<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashReconciliation;
use App\Models\Branch;
use App\Services\NotificationService;
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

        $perPage = (int) $request->input('itemsPerPage', 10);
        return response()->json($query->paginate($perPage));
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

    /**
     * Preview calculation of expected cash including capital installments and petty cash
     */
    public function preview(Request $request)
    {
        $branchId = $request->query('branch_id');
        $date = $request->query('date', Carbon::today()->toDateString());

        if (!$branchId) {
            return response()->json(['error' => 'Branch ID required'], 400);
        }

        $breakdown = $this->calculateCashComponents($branchId, $date);

        return response()->json([
            'success' => true,
            'date' => $date,
            'branch_id' => $branchId,
            'breakdown' => $breakdown,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id'   => 'required|exists:branches,id',
            'actual_cash' => 'required|numeric|min:0',
            'notes'       => 'nullable|string',
            'date'        => 'required|date',
            'status'      => 'nullable|in:draft,completed',
        ]);

        $date = $validated['date'];
        $user = $request->user();

        // Prevent duplicate closing for the same branch on the same date
        $existing = CashReconciliation::where('branch_id', $validated['branch_id'])
            ->where('date', $date)
            ->first();
            
        if ($existing) {
            return response()->json(['message' => 'Laporan closing untuk tanggal ini sudah dibuat. Silakan periksa tabel riwayat di bawah untuk mengeditnya.'], 400);
        }

        $calc = $this->calculateCashComponents($validated['branch_id'], $date);
        $expectedCash = $calc['expected_cash'];
        $variance = $validated['actual_cash'] - $expectedCash;

        $reconciliation = CashReconciliation::create([
            'branch_id'                  => $validated['branch_id'],
            'user_id'                    => $user->id,
            'date'                       => $date,
            'expected_cash'              => $expectedCash,
            'cash_sales_amount'          => $calc['cash_sales_amount'],
            'dp_cash_amount'             => $calc['dp_cash_amount'],
            'receivable_payments_amount' => $calc['receivable_payments_amount'],
            'capital_returns_amount'     => $calc['capital_returns_amount'],
            'capital_injections_amount'  => $calc['capital_injections_amount'],
            'petty_cash_amount'          => $calc['petty_cash_amount'],
            'actual_cash'                => $validated['actual_cash'],
            'variance'                   => $variance,
            'status'                     => $request->status ?? 'completed',
            'notes'                      => $validated['notes'] ?? '',
        ]);

        $branch = Branch::find($validated['branch_id']);
        $branchName = $branch ? $branch->name : 'Cabang';
        $formattedActual = 'Rp ' . number_format($validated['actual_cash'], 0, ',', '.');
        $formattedVariance = 'Rp ' . number_format($variance, 0, ',', '.');

        NotificationService::notifyUser(
            $user->id,
            $variance != 0 ? 'Closing Kasir Disimpan (Ada Selisih)' : 'Closing Kasir Berhasil Disimpan',
            "Closing kasir cabang {$branchName} tanggal {$date} telah disimpan (Fisik: {$formattedActual}" . ($variance != 0 ? ", Selisih: {$formattedVariance}" : "") . ").",
            '/audit/closing-harian',
            $variance != 0 ? 'warning' : 'success',
            $variance != 0 ? 'ri-error-warning-line' : 'ri-checkbox-circle-line',
            $validated['branch_id']
        );

        NotificationService::notifyBranch(
            $validated['branch_id'],
            'Closing Harian Diselesaikan',
            "Laporan closing harian tanggal {$date} telah selesai disimpan oleh {$user->name}.",
            '/audit/closing-harian',
            'info',
            'ri-safe-2-line'
        );

        if ($variance != 0) {
            NotificationService::notifyOwnerAndAdmins(
                '⚠️ Alert Selisih Kas Closing Harian',
                "Closing kasir cabang {$branchName} tanggal {$date} terdapat SELISIH kas sebesar {$formattedVariance} (Fisik: {$formattedActual}).",
                '/audit/closing-harian',
                'error',
                'ri-error-warning-line',
                $validated['branch_id']
            );
        } else {
            NotificationService::notifyOwnerAndAdmins(
                'Closing Kasir Selesai (Seimbang)',
                "Closing kasir cabang {$branchName} tanggal {$date} berhasil diselesaikan (Kas Fisik: {$formattedActual} - Sesuai).",
                '/audit/closing-harian',
                'info',
                'ri-safe-2-line',
                $validated['branch_id']
            );
        }

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
            'notes'       => 'nullable|string',
            'status'      => 'nullable|in:draft,completed',
        ]);

        $calc = $this->calculateCashComponents($reconciliation->branch_id, $reconciliation->date);
        $expectedCash = $calc['expected_cash'];
        $variance = $validated['actual_cash'] - $expectedCash;

        $reconciliation->update([
            'expected_cash'              => $expectedCash,
            'cash_sales_amount'          => $calc['cash_sales_amount'],
            'dp_cash_amount'             => $calc['dp_cash_amount'],
            'receivable_payments_amount' => $calc['receivable_payments_amount'],
            'capital_returns_amount'     => $calc['capital_returns_amount'],
            'capital_injections_amount'  => $calc['capital_injections_amount'],
            'petty_cash_amount'          => $calc['petty_cash_amount'],
            'actual_cash'                => $validated['actual_cash'],
            'variance'                   => $variance,
            'status'                     => $request->status ?? $reconciliation->status,
            'notes'                      => $validated['notes'] ?? $reconciliation->notes,
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

    /**
     * Helper to compute all cash components for a branch and date
     */
    protected function calculateCashComponents(int $branchId, string $date): array
    {
        // 1. Penjualan Tunai Murni (+)
        $cashSales = DB::table('sales')
            ->where('branch_id', $branchId)
            ->whereDate('date', $date)
            ->where('status', '!=', 'cancelled')
            ->where('payment_method', 'cash')
            ->sum(DB::raw('paid_amount - change_amount'));

        // 2. DP Penjualan Tempo Tunai (+)
        $dpCashSales = DB::table('sales')
            ->where('branch_id', $branchId)
            ->whereDate('date', $date)
            ->where('status', '!=', 'cancelled')
            ->where('payment_method', 'tempo')
            ->whereNull('bank_name')
            ->sum('paid_amount');

        // 3. Pelunasan Piutang Tunai (+)
        $receivableCashPayments = DB::table('receivable_payments')
            ->join('receivables', 'receivable_payments.receivable_id', '=', 'receivables.id')
            ->where('receivables.branch_id', $branchId)
            ->whereDate('receivable_payments.payment_date', $date)
            ->where('receivable_payments.payment_method', 'cash')
            ->sum('receivable_payments.amount');

        // 4. Injeksi / Tambahan Modal Masuk Tunai (+)
        $capitalInjections = DB::table('branch_capitals')
            ->where('branch_id', $branchId)
            ->whereDate('date', $date)
            ->where('type', 'injection')
            ->whereNotIn('status', ['rejected', 'void'])
            ->where(function ($q) {
                $q->where('payment_method', 'Kas Tunai')
                  ->orWhere('payment_method', 'cash')
                  ->orWhereNull('payment_method');
            })
            ->sum('amount');

        // 5. Setoran / Cicilan Pengembalian Modal ke Owner Tunai (-)
        $capitalReturns = DB::table('branch_capitals')
            ->where('branch_id', $branchId)
            ->whereDate('date', $date)
            ->where('type', 'return')
            ->whereNotIn('status', ['rejected', 'void'])
            ->where(function ($q) {
                $q->where('payment_method', 'Kas Tunai')
                  ->orWhere('payment_method', 'cash')
                  ->orWhereNull('payment_method');
            })
            ->sum('amount');

        // 6. Pengeluaran Kas Kecil Operasional Tunai (-)
        $pettyCashAmount = DB::table('petty_cashes')
            ->where('branch_id', $branchId)
            ->whereDate('date', $date)
            ->sum('amount');

        $expectedCash = ($cashSales + $dpCashSales + $receivableCashPayments + $capitalInjections) - ($capitalReturns + $pettyCashAmount);

        return [
            'cash_sales_amount'          => (float) $cashSales,
            'dp_cash_amount'             => (float) $dpCashSales,
            'receivable_payments_amount' => (float) $receivableCashPayments,
            'capital_injections_amount'  => (float) $capitalInjections,
            'capital_returns_amount'     => (float) $capitalReturns,
            'petty_cash_amount'          => (float) $pettyCashAmount,
            'expected_cash'              => (float) $expectedCash,
        ];
    }
}
