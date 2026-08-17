<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashReconciliation;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CashReconciliationController extends Controller
{
    public function index(Request $request)
    {
        $query = CashReconciliation::with(['user:id,name', 'branch:id,name'])->orderBy('date', 'desc');

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->query('branch_id'));
        }
        
        $user = $request->user();
        if ($user && $user->active_role_id) {
            $role = \Spatie\Permission\Models\Role::find($user->active_role_id);
            if (!$role || !$role->hasPermissionTo('Cabang Read')) {
                $assignment = DB::table('model_has_roles')
                    ->where('model_id', $user->id)
                    ->where('model_type', get_class($user))
                    ->where('role_id', $user->active_role_id)
                    ->first();
                if ($assignment && $assignment->branch_id) {
                    $query->where('branch_id', $assignment->branch_id);
                }
            }
        }

        return response()->json($query->paginate(10));
    }

    public function monitoring(Request $request)
    {
        $date = $request->query('date', Carbon::today()->toDateString());
        
        $branches = \App\Models\Branch::where('status', 'Aktif')->get();
        
        $closings = CashReconciliation::with('user:id,name')
            ->whereDate('date', $date)
            ->get()
            ->keyBy('branch_id');
            
        $result = $branches->map(function ($branch) use ($closings) {
            $closing = $closings->get($branch->id);
            return [
                'branch_id' => $branch->id,
                'branch_name' => $branch->name,
                'is_closed' => $closing ? true : false,
                'closed_at' => $closing ? $closing->created_at : null,
                'closed_by' => $closing ? $closing->user->name : null,
                'variance' => $closing ? $closing->variance : null,
                'expected_cash' => $closing ? $closing->expected_cash : null,
                'actual_cash' => $closing ? $closing->actual_cash : null,
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $result,
            'date' => $date
        ]);
    }

    public function getRequiredDate(Request $request)
    {
        $branchId = $request->query('branch_id');
        if (!$branchId) {
            return response()->json(['error' => 'Branch ID required'], 400);
        }

        $lastClosing = CashReconciliation::where('branch_id', $branchId)
            ->orderBy('date', 'desc')
            ->first();
            
        $today = Carbon::today()->toDateString();
        
        if ($lastClosing) {
            $nextRequired = Carbon::parse($lastClosing->date)->addDay()->toDateString();
            if ($nextRequired > $today) {
                $nextRequired = $today;
            }
            return response()->json(['date' => $nextRequired, 'last_closed' => $lastClosing->date]);
        }
        
        return response()->json(['date' => $today, 'last_closed' => null]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'actual_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'date' => 'required|date',
            'status' => 'nullable|in:draft,completed',
        ]);

        $date = $validated['date'];
        $today = Carbon::today()->toDateString();
        $user = $request->user();

        // Mencegah duplikasi closing di hari yang sama
        $existing = CashReconciliation::where('branch_id', $validated['branch_id'])
            ->where('date', $date)
            ->first();
            
        if ($existing) {
            return response()->json(['message' => 'Laporan closing untuk tanggal ini sudah dibuat. Silakan periksa tabel riwayat di bawah untuk mengeditnya.'], 400);
        }

        // Calculate expected cash (Fisik Laci Kasir)
        $cashSales = DB::table('sales')
            ->where('branch_id', $validated['branch_id'])
            ->where('user_id', $user->id)
            ->whereDate('date', $date)
            ->where('status', '!=', 'cancelled')
            ->where('payment_method', 'cash')
            ->sum(DB::raw('paid_amount - change_amount'));

        $dpCashSales = DB::table('sales')
            ->where('branch_id', $validated['branch_id'])
            ->where('user_id', $user->id)
            ->whereDate('date', $date)
            ->where('status', '!=', 'cancelled')
            ->where('payment_method', 'tempo')
            ->whereNull('bank_name') // Asumsi DP tunai jika tidak ada bank
            ->sum('paid_amount');

        $receivableCashPayments = DB::table('receivable_payments')
            ->join('receivables', 'receivable_payments.receivable_id', '=', 'receivables.id')
            ->where('receivables.branch_id', $validated['branch_id'])
            ->where('receivable_payments.user_id', $user->id)
            ->whereDate('receivable_payments.payment_date', $date)
            ->where('receivable_payments.payment_method', 'cash')
            ->sum('receivable_payments.amount');

        $expectedCash = $cashSales + $dpCashSales + $receivableCashPayments;

        $variance = $validated['actual_cash'] - $expectedCash;

        $reconciliation = CashReconciliation::create([
            'branch_id' => $validated['branch_id'],
            'user_id' => $user->id,
            'date' => $date,
            'expected_cash' => $expectedCash,
            'actual_cash' => $validated['actual_cash'],
            'variance' => $variance,
            'status' => $request->status ?? 'completed',
            'notes' => $validated['notes'] ?? '',
        ]);

        return response()->json([
            'message' => 'Cash reconciliation submitted successfully.',
            'data' => $reconciliation
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $reconciliation = CashReconciliation::findOrFail($id);



        $validated = $request->validate([
            'actual_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:draft,completed',
        ]);

        $variance = $validated['actual_cash'] - $reconciliation->expected_cash;

        $reconciliation->update([
            'actual_cash' => $validated['actual_cash'],
            'variance' => $variance,
            'status' => $request->status ?? $reconciliation->status,
            'notes' => $validated['notes'] ?? ''
        ]);

        return response()->json([
            'message' => 'Closing harian berhasil diperbarui',
            'data' => $reconciliation
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $reconciliation = CashReconciliation::findOrFail($id);



        $reconciliation->delete();

        return response()->json(['message' => 'Data closing berhasil dihapus']);
    }
}
