<?php

namespace App\Http\Controllers;

use App\Models\Receivable;
use App\Models\ReceivablePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReceivableController extends Controller
{
    public function index(Request $request)
    {
        $query = Receivable::with(['customer', 'sale.branch', 'sale.user']);

        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        if ($request->has('branch_id') && !empty($request->branch_id)) {
            $query->whereHas('sale', function($s) use ($request) {
                $s->where('branch_id', $request->branch_id);
            });
        }

        $search = $request->query('search', $request->query('q'));
        if (!empty($search)) {
            $query->where(function($subQuery) use ($search) {
                $subQuery->whereHas('customer', function($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%")
                       ->orWhere('phone', 'like', "%{$search}%");
                })->orWhereHas('sale', function($q3) use ($search) {
                    $q3->where('invoice_number', 'like', "%{$search}%");
                });
            });
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('due_date', [$request->start_date, $request->end_date]);
        }

        // Calculate summary KPI
        $summaryQuery = clone $query;
        $summaryQuery->reorder();
        $allReceivables = $summaryQuery->get();

        $totalDue = $allReceivables->sum('amount_due');
        $totalPaid = $allReceivables->sum('amount_paid');
        $totalRemaining = $totalDue - $totalPaid;
        $today = now()->toDateString();
        $totalOverdue = $allReceivables->filter(function($r) use ($today) {
            return $r->status !== 'paid' && $r->due_date && $r->due_date < $today;
        })->sum(function($r) {
            return $r->amount_due - $r->amount_paid;
        });

        $summary = [
            'total_due' => (float) $totalDue,
            'total_paid' => (float) $totalPaid,
            'total_remaining' => (float) $totalRemaining,
            'total_overdue' => (float) $totalOverdue,
            'count_unpaid' => $allReceivables->whereIn('status', ['unpaid', 'partial'])->count(),
            'count_paid' => $allReceivables->where('status', 'paid')->count(),
        ];

        $itemsPerPage = (int) $request->input('itemsPerPage', 15);
        $query->orderBy('due_date', 'asc');

        if ($itemsPerPage === -1) {
            $receivables = $query->get();
            $paginated = null;
        } else {
            $paginated = $query->paginate($itemsPerPage);
            $receivables = $paginated->items();
        }

        $response = [
            'data' => $receivables,
            'summary' => $summary,
        ];

        if ($paginated) {
            $response['current_page'] = $paginated->currentPage();
            $response['last_page'] = $paginated->lastPage();
            $response['per_page'] = $paginated->perPage();
            $response['total'] = $paginated->total();
        }
        
        return response()->json($response);
    }

    public function show(Receivable $receivable)
    {
        $receivable->load(['customer', 'sale.items.productBranch.product', 'payments.user']);
        return response()->json($receivable);
    }

    public function pay(Request $request, Receivable $receivable)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string',
            'payment_date' => 'required|date',
            'payment_proof' => 'nullable|image|max:5120',
            'bank_name' => 'nullable|string',
            'bank_account_number' => 'nullable|string',
            'bank_account_name' => 'nullable|string',
            'transfer_phone_number' => 'nullable|string',
        ]);

        if ($receivable->status === 'paid') {
            return response()->json(['message' => 'This receivable is already fully paid.'], 400);
        }

        $remainingBalance = $receivable->amount_due - $receivable->amount_paid;
        
        if ($validated['amount'] > $remainingBalance) {
            return response()->json([
                'message' => 'Payment amount cannot exceed remaining balance.',
                'remaining' => $remainingBalance
            ], 400);
        }

        DB::beginTransaction();
        try {
            $proofPath = null;
            if ($request->hasFile('payment_proof')) {
                $proofPath = $request->file('payment_proof')->store('payment_proofs', 'public');
            }

            // Create payment record
            $payment = ReceivablePayment::create([
                'receivable_id' => $receivable->id,
                'payment_date' => $validated['payment_date'],
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'payment_proof' => $proofPath,
                'bank_name' => $validated['bank_name'] ?? null,
                'bank_account_number' => $validated['bank_account_number'] ?? null,
                'bank_account_name' => $validated['bank_account_name'] ?? null,
                'transfer_phone_number' => $validated['transfer_phone_number'] ?? null,
                'user_id' => auth()->id(),
            ]);

            // Update receivable amounts and status
            $receivable->amount_paid += $validated['amount'];
            
            if ($receivable->amount_paid >= $receivable->amount_due) {
                $receivable->status = 'paid';
            } else {
                $receivable->status = 'partial';
            }
            
            $receivable->save();

            DB::commit();

            return response()->json([
                'message' => 'Payment processed successfully.',
                'payment' => $payment,
                'receivable' => $receivable
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to process payment.', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request, Receivable $receivable)
    {
        // Delete Receivable will actually void the entire Sale transaction.
        // We will call the SaleController's destroy logic.
        $sale = $receivable->sale;
        
        if (!$sale) {
            return response()->json(['message' => 'Transaksi Penjualan tidak ditemukan'], 404);
        }

        // We can just simulate a request to SaleController@destroy
        $saleController = new \App\Http\Controllers\Api\SaleController();
        return $saleController->destroy($request, $sale->id);
    }
}
