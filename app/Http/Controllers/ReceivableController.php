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

        if ($request->has('q') && !empty($request->q)) {
            $q = $request->q;
            $query->whereHas('customer', function($q2) use ($q) {
                $q2->where('name', 'like', "%{$q}%");
            })->orWhereHas('sale', function($q3) use ($q) {
                $q3->where('invoice_number', 'like', "%{$q}%");
            });
        }

        $itemsPerPage = $request->input('itemsPerPage', 15);
        $receivables = $query->orderBy('due_date', 'asc')->paginate($itemsPerPage);
        
        return response()->json($receivables);
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
