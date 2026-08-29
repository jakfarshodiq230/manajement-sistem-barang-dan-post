<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BankAccount;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class BankAccountController extends Controller
{
    /**
     * Display a listing of the resource with Year and Month analytics.
     */
    public function index(Request $request)
    {
        $year = (int) $request->query('year', date('Y'));
        $month = $request->has('month') && $request->month !== '' && $request->month !== null ? (int) $request->month : (int) date('n');

        $query = BankAccount::with('branch:id,name');

        if ($request->has('branch_id') && $request->branch_id !== null && $request->branch_id !== '') {
            $query->where(function ($q) use ($request) {
                $q->where('branch_id', $request->branch_id)
                  ->orWhereNull('branch_id'); // Global accounts available to all branches
            });
        }

        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('bank_name', 'like', "%{$search}%")
                  ->orWhere('account_number', 'like', "%{$search}%")
                  ->orWhere('account_name', 'like', "%{$search}%");
            });
        }

        $accounts = $query->orderBy('is_default', 'desc')
            ->orderBy('is_active', 'desc')
            ->orderBy('bank_name', 'asc')
            ->get();

        // Calculate Monthly & Yearly Stats for each bank account
        foreach ($accounts as $account) {
            // Selected Month Revenue
            $account->month_received = (float) DB::table('sales')
                ->where('bank_account_id', $account->id)
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->where('status', '!=', 'cancelled')
                ->sum('total_amount');

            $account->month_tx_count = DB::table('sales')
                ->where('bank_account_id', $account->id)
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->where('status', '!=', 'cancelled')
                ->count();

            // Selected Year Revenue
            $account->year_received = (float) DB::table('sales')
                ->where('bank_account_id', $account->id)
                ->whereYear('date', $year)
                ->where('status', '!=', 'cancelled')
                ->sum('total_amount');

            // 12-Month Trend Array for this bank in selected year
            $monthly12 = [];
            for ($m = 1; $m <= 12; $m++) {
                $monthly12[$m] = (float) DB::table('sales')
                    ->where('bank_account_id', $account->id)
                    ->whereYear('date', $year)
                    ->whereMonth('date', $m)
                    ->where('status', '!=', 'cancelled')
                    ->sum('total_amount');
            }
            $account->monthly_trend = array_values($monthly12);
        }

        // Available Years from Sales Data
        $salesYears = DB::table('sales')
            ->selectRaw('DISTINCT YEAR(date) as yr')
            ->whereNotNull('date')
            ->pluck('yr')
            ->toArray();

        $currentYear = (int) date('Y');
        $yearsList = array_unique(array_merge([$currentYear - 1, $currentYear, $currentYear + 1], array_map('intval', $salesYears)));
        rsort($yearsList);

        // Overall Monthly & Yearly Summary KPI
        $selectedMonthReceived = (float) DB::table('sales')
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->whereNotNull('bank_account_id')
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');

        $selectedMonthTxCount = DB::table('sales')
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->whereNotNull('bank_account_id')
            ->where('status', '!=', 'cancelled')
            ->count();

        $selectedYearReceived = (float) DB::table('sales')
            ->whereYear('date', $year)
            ->whereNotNull('bank_account_id')
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');

        $summary = [
            'total_accounts'          => BankAccount::count(),
            'active_accounts'         => BankAccount::where('is_active', true)->count(),
            'total_balance'           => (float) BankAccount::where('is_active', true)->sum('current_balance'),
            'selected_year'           => $year,
            'selected_month'          => $month,
            'selected_month_received' => $selectedMonthReceived,
            'selected_month_tx_count' => $selectedMonthTxCount,
            'selected_year_received'  => $selectedYearReceived,
            'available_years'         => $yearsList,
        ];

        return response()->json([
            'data'    => $accounts,
            'summary' => $summary,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user() ?: auth()->user();

        $request->validate([
            'bank_name'       => 'required|string|max:100',
            'account_number'  => 'nullable|string|max:100',
            'account_name'    => 'nullable|string|max:150',
            'type'            => 'required|in:bank_transfer,qris,edc_debit,edc_credit,cash',
            'branch_id'       => 'nullable|exists:branches,id',
            'initial_balance' => 'nullable|numeric|min:0',
            'qris_image'      => 'nullable|image|max:3072',
            'is_active'       => 'nullable|boolean',
            'is_default'      => 'nullable|boolean',
            'color'           => 'nullable|string|max:20',
            'notes'           => 'nullable|string',
        ]);

        $qrisPath = null;
        if ($request->hasFile('qris_image')) {
            $file = $request->file('qris_image');
            if ($file->isValid()) {
                $qrisPath = $file->store('bank_qris', 'public');
            }
        }

        // If setting as default, unset other defaults
        if ($request->is_default) {
            BankAccount::where('is_default', true)->update(['is_default' => false]);
        }

        $initialBalance = (float) ($request->initial_balance ?? 0);

        $bankAccount = BankAccount::create([
            'bank_name'       => $request->bank_name,
            'account_number'  => $request->account_number,
            'account_name'    => $request->account_name,
            'type'            => $request->type,
            'branch_id'       => $request->branch_id ?: null,
            'initial_balance' => $initialBalance,
            'current_balance' => $initialBalance,
            'qris_image'      => $qrisPath ? '/storage/' . $qrisPath : null,
            'is_active'       => $request->has('is_active') ? filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN) : true,
            'is_default'      => $request->has('is_default') ? filter_var($request->is_default, FILTER_VALIDATE_BOOLEAN) : false,
            'color'           => $request->color ?: '#0066AE',
            'notes'           => $request->notes,
            'created_by'      => $user ? $user->id : null,
        ]);

        return response()->json([
            'message' => 'Rekening Bank berhasil ditambahkan.',
            'data'    => $bankAccount->load('branch:id,name'),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $bankAccount = BankAccount::with('branch:id,name')->findOrFail($id);

        // Recent sales using this bank account with branch and cashier info
        $recentSales = DB::table('sales')
            ->leftJoin('branches', 'sales.branch_id', '=', 'branches.id')
            ->leftJoin('users', 'sales.user_id', '=', 'users.id')
            ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
            ->where('sales.bank_account_id', $bankAccount->id)
            ->where('sales.status', '!=', 'cancelled')
            ->select(
                'sales.id',
                'sales.invoice_number',
                'sales.date',
                'sales.total_amount',
                'sales.payment_method',
                'sales.status',
                'sales.created_at',
                'branches.name as branch_name',
                'users.name as cashier_name',
                'customers.name as customer_name'
            )
            ->orderBy('sales.created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'data'         => $bankAccount,
            'recent_sales' => $recentSales,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $bankAccount = BankAccount::findOrFail($id);

        $request->validate([
            'bank_name'       => 'required|string|max:100',
            'account_number'  => 'nullable|string|max:100',
            'account_name'    => 'nullable|string|max:150',
            'type'            => 'required|in:bank_transfer,qris,edc_debit,edc_credit,cash',
            'branch_id'       => 'nullable|exists:branches,id',
            'initial_balance' => 'nullable|numeric|min:0',
            'current_balance' => 'nullable|numeric',
            'qris_image'      => 'nullable',
            'is_active'       => 'nullable|boolean',
            'is_default'      => 'nullable|boolean',
            'color'           => 'nullable|string|max:20',
            'notes'           => 'nullable|string',
        ]);

        $qrisPath = $bankAccount->qris_image;
        if ($request->hasFile('qris_image')) {
            $file = $request->file('qris_image');
            if ($file->isValid()) {
                $qrisPath = '/storage/' . $file->store('bank_qris', 'public');
            }
        }

        if ($request->is_default && !$bankAccount->is_default) {
            BankAccount::where('id', '!=', $bankAccount->id)->where('is_default', true)->update(['is_default' => false]);
        }

        $bankAccount->update([
            'bank_name'       => $request->bank_name,
            'account_number'  => $request->account_number,
            'account_name'    => $request->account_name,
            'type'            => $request->type,
            'branch_id'       => $request->branch_id ?: null,
            'initial_balance' => $request->has('initial_balance') ? (float) $request->initial_balance : $bankAccount->initial_balance,
            'current_balance' => $request->has('current_balance') ? (float) $request->current_balance : $bankAccount->current_balance,
            'qris_image'      => $qrisPath,
            'is_active'       => $request->has('is_active') ? filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN) : $bankAccount->is_active,
            'is_default'      => $request->has('is_default') ? filter_var($request->is_default, FILTER_VALIDATE_BOOLEAN) : $bankAccount->is_default,
            'color'           => $request->color ?: $bankAccount->color,
            'notes'           => $request->notes,
        ]);

        return response()->json([
            'message' => 'Rekening Bank berhasil diperbarui.',
            'data'    => $bankAccount->load('branch:id,name'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $bankAccount = BankAccount::findOrFail($id);

        // Check if there are sales transactions
        $salesCount = DB::table('sales')->where('bank_account_id', $bankAccount->id)->count();
        if ($salesCount > 0) {
            // Soft deactivate instead of hard delete
            $bankAccount->update(['is_active' => false]);
            return response()->json([
                'message' => 'Rekening bank memiliki riwayat ' . $salesCount . ' transaksi dan telah dinonaktifkan.',
            ]);
        }

        $bankAccount->delete();

        return response()->json([
            'message' => 'Rekening bank berhasil dihapus.',
        ]);
    }
}
