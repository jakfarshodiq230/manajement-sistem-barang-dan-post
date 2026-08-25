<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashShift;
use App\Models\Sale;
use App\Models\PettyCash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CashShiftController extends Controller
{
    /**
     * Get the current active cash shift for the authenticated user / branch
     */
    public function current(Request $request)
    {
        $user = $request->user();
        $branchId = $user->branch_id ?: ($request->header('X-Branch-Id') ?: 1);

        $shift = CashShift::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        if (!$shift) {
            return response()->json([
                'has_active_shift' => false,
                'shift' => null,
            ]);
        }

        // Calculate real-time stats for this shift
        $salesQuery = Sale::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $shift->opened_at);

        if ($shift->branch_id) {
            $salesQuery->where('branch_id', $shift->branch_id);
        }

        $cashSales = (clone $salesQuery)
            ->where('payment_method', 'cash')
            ->sum('total_amount');

        $nonCashSales = (clone $salesQuery)
            ->where('payment_method', '!=', 'cash')
            ->sum('total_amount');

        $expenses = PettyCash::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $shift->opened_at)
            ->sum('amount');

        $expectedCash = (float) $shift->start_cash + (float) $cashSales - (float) $expenses;

        return response()->json([
            'has_active_shift' => true,
            'shift' => $shift,
            'summary' => [
                'start_cash' => (float) $shift->start_cash,
                'total_cash_sales' => (float) $cashSales,
                'total_non_cash_sales' => (float) $nonCashSales,
                'total_sales' => (float) $cashSales + (float) $nonCashSales,
                'total_expenses' => (float) $expenses,
                'expected_cash' => $expectedCash,
                'opened_at' => $shift->opened_at ? $shift->opened_at->format('Y-m-d H:i:s') : date('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Open a new cash shift
     */
    public function open(Request $request)
    {
        $request->validate([
            'start_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
            'branch_id' => 'nullable|integer',
        ]);

        $user = $request->user();
        $branchId = $request->branch_id ?: ($user->branch_id ?: ($request->header('X-Branch-Id') ?: 1));

        // Check if there is already an open shift
        $existing = CashShift::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Shift kasir sudah aktif.',
                'shift' => $existing,
            ], 200);
        }

        $shift = CashShift::create([
            'branch_id' => $branchId,
            'user_id' => $user->id,
            'start_cash' => $request->start_cash,
            'status' => 'open',
            'opened_at' => Carbon::now(),
            'notes' => $request->notes,
        ]);

        return response()->json([
            'message' => 'Shift kasir berhasil dibuka.',
            'shift' => $shift,
        ], 201);
    }

    /**
     * Close the current active cash shift
     */
    public function close(Request $request)
    {
        $request->validate([
            'actual_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $shift = CashShift::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        if (!$shift) {
            return response()->json(['message' => 'Tidak ada shift aktif yang ditemukan.'], 404);
        }

        $salesQuery = Sale::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $shift->opened_at);

        if ($shift->branch_id) {
            $salesQuery->where('branch_id', $shift->branch_id);
        }

        $cashSales = (clone $salesQuery)->where('payment_method', 'cash')->sum('total_amount');
        $nonCashSales = (clone $salesQuery)->where('payment_method', '!=', 'cash')->sum('total_amount');
        $expenses = PettyCash::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $shift->opened_at)
            ->sum('amount');

        $expectedCash = (float) $shift->start_cash + (float) $cashSales - (float) $expenses;
        $actualCash = (float) $request->actual_cash;
        $difference = $actualCash - $expectedCash;

        $shift->update([
            'total_cash_sales' => $cashSales,
            'total_non_cash_sales' => $nonCashSales,
            'total_expenses' => $expenses,
            'expected_cash' => $expectedCash,
            'actual_cash' => $actualCash,
            'difference' => $difference,
            'status' => 'closed',
            'closed_at' => Carbon::now(),
            'notes' => $request->notes ?? $shift->notes,
        ]);

        // Optional: Automatic Capital Return to Owner from Closing Shift Cash
        $capitalReturn = null;
        if ($request->filled('capital_return_amount') && (float) $request->capital_return_amount > 0) {
            $proofPath = null;
            if ($request->hasFile('proof_file')) {
                $proofPath = $request->file('proof_file')->store('branch_capitals', 'public');
            }

            $capitalReturn = \App\Models\BranchCapital::create([
                'reference_no' => 'CAP-RET-' . date('Ym') . '-' . strtoupper(substr(uniqid(), -5)),
                'branch_id' => $shift->branch_id ?: ($user->branch_id ?: 1),
                'cash_shift_id' => $shift->id,
                'user_id' => $user->id,
                'type' => 'return',
                'category' => 'Setoran Laba Closing Shift',
                'amount' => (float) $request->capital_return_amount,
                'date' => Carbon::now()->toDateString(),
                'payment_method' => $request->payment_method ?: 'Transfer Bank',
                'bank_name' => $request->bank_name,
                'account_number' => $request->account_number,
                'account_name' => $request->account_name,
                'proof_file' => $proofPath,
                'notes' => 'Setoran pengembalian modal dari Closing Shift #' . $shift->id . ($request->notes ? " - " . $request->notes : ""),
                'status' => 'pending',
            ]);
        }

        return response()->json([
            'message' => 'Shift kasir berhasil ditutup.',
            'shift' => $shift,
            'summary' => [
                'start_cash' => (float) $shift->start_cash,
                'total_cash_sales' => (float) $cashSales,
                'total_non_cash_sales' => (float) $nonCashSales,
                'total_expenses' => (float) $expenses,
                'expected_cash' => $expectedCash,
                'actual_cash' => $actualCash,
                'difference' => $difference,
                'opened_at' => $shift->opened_at->format('Y-m-d H:i:s'),
                'closed_at' => $shift->closed_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * List historical cash shifts
     */
    public function index(Request $request)
    {
        $branchId = $request->query('branch_id');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = CashShift::with(['user:id,name,email', 'branch:id,name'])->latest('opened_at');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('opened_at', ["$startDate 00:00:00", "$endDate 23:59:59"]);
        }

        $shifts = $query->paginate($request->query('itemsPerPage', 15));

        return response()->json($shifts);
    }
}
