<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payable;
use App\Models\PayablePayment;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\SupplierCredit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PayableController extends Controller
{
    /**
     * Display a listing of Accounts Payable with financial KPI summary.
     */
    public function index(Request $request)
    {
        // Auto sync approved goods receipts if table is empty or on demand
        $this->autoSyncFromGoodsReceipts();

        $query = Payable::with([
            'supplier',
            'branch',
            'purchaseOrder',
            'goodsReceipt.items.productBranch.product',
            'payments.creator',
            'creator',
        ]);

        if ($request->has('supplier_id') && !empty($request->supplier_id)) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->has('branch_id') && !empty($request->branch_id)) {
            $query->where('branch_id', $request->branch_id);
        }

        $today = now()->toDateString();

        if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
            if ($request->status === 'overdue') {
                $query->where('status', '!=', 'paid')
                      ->whereNotNull('due_date')
                      ->where('due_date', '<', $today);
            } else {
                $query->where('status', $request->status);
            }
        }

        $search = $request->query('search', $request->query('q'));
        if (!empty($search)) {
            $query->where(function($subQuery) use ($search) {
                $subQuery->where('payable_number', 'like', "%{$search}%")
                         ->orWhere('invoice_number_supplier', 'like', "%{$search}%")
                         ->orWhereHas('supplier', function($qSupplier) use ($search) {
                             $qSupplier->where('name', 'like', "%{$search}%")
                                      ->orWhere('phone', 'like', "%{$search}%");
                         })
                         ->orWhereHas('purchaseOrder', function($qPo) use ($search) {
                             $qPo->where('po_number', 'like', "%{$search}%");
                         });
            });
        }

        if ($request->has('start_date') && $request->has('end_date') && !empty($request->start_date) && !empty($request->end_date)) {
            $query->whereBetween('due_date', [$request->start_date, $request->end_date]);
        }

        // Calculate summary KPIs across filtered set
        $summaryQuery = clone $query;
        $summaryQuery->reorder();
        $allPayables = $summaryQuery->get();

        $totalPayable = (float) $allPayables->sum('total_amount');
        $totalPaid = (float) $allPayables->sum('paid_amount');
        $totalRemaining = (float) $allPayables->sum('remaining_amount');

        $totalOverdue = (float) $allPayables->filter(function($p) use ($today) {
            return $p->status !== 'paid' && $p->due_date && $p->due_date < $today;
        })->sum('remaining_amount');

        $dueSoon = (float) $allPayables->filter(function($p) use ($today) {
            $next7Days = now()->addDays(7)->toDateString();
            return $p->status !== 'paid' && $p->due_date && $p->due_date >= $today && $p->due_date <= $next7Days;
        })->sum('remaining_amount');

        $summary = [
            'total_payable' => $totalPayable,
            'total_paid' => $totalPaid,
            'total_remaining' => $totalRemaining,
            'total_overdue' => $totalOverdue,
            'total_due_soon' => $dueSoon,
            'count_unpaid' => $allPayables->where('status', 'unpaid')->count(),
            'count_partial' => $allPayables->where('status', 'partial')->count(),
            'count_paid' => $allPayables->where('status', 'paid')->count(),
            'count_overdue' => $allPayables->filter(function($p) use ($today) {
                return $p->status !== 'paid' && $p->due_date && $p->due_date < $today;
            })->count(),
            'count_total' => $allPayables->count(),
        ];

        $itemsPerPage = (int) $request->input('itemsPerPage', 15);
        $query->orderBy('due_date', 'asc')->orderBy('created_at', 'desc');

        if ($itemsPerPage === -1) {
            $payables = $query->get();
            $paginated = null;
        } else {
            $paginated = $query->paginate($itemsPerPage);
            $payables = $paginated->items();
        }

        $response = [
            'data' => $payables,
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

    /**
     * Display the specified Payable with detail items & payments history.
     */
    public function show($id)
    {
        $payable = Payable::with([
            'supplier',
            'branch',
            'purchaseOrder.items.product',
            'goodsReceipt.items.productBranch.product',
            'payments.creator',
            'payments.supplierCredit',
            'creator',
        ])->findOrFail($id);

        return response()->json($payable);
    }

    /**
     * Record an installment or full payment towards the payable.
     */
    public function recordPayment(Request $request, $id)
    {
        $user = $request->user() ?: auth()->user();
        $payable = Payable::findOrFail($id);

        $request->validate([
            'amount' => 'required|numeric|min:1|max:' . ($payable->remaining_amount + 0.01),
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:bank_transfer,cash,giro_cheque,supplier_credit',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:100',
            'bank_account_name' => 'nullable|string|max:100',
            'reference_number' => 'nullable|string|max:100',
            'proof_file' => 'nullable',
            'supplier_credit_id' => 'nullable|exists:supplier_credits,id',
            'notes' => 'nullable|string',
        ]);

        $amount = (float) $request->amount;
        if ($amount <= 0) {
            return response()->json(['message' => 'Nominal pembayaran harus lebih besar dari 0.'], 422);
        }

        if ($amount > (float) $payable->remaining_amount) {
            return response()->json(['message' => 'Nominal pembayaran melebihi sisa hutang (Rp ' . number_format($payable->remaining_amount, 0, ',', '.') . ').'], 422);
        }

        DB::beginTransaction();
        try {
            $proofPath = null;
            if ($request->hasFile('proof_file')) {
                $file = $request->file('proof_file');
                if ($file->isValid()) {
                    $proofPath = $file->store('payable_proofs', 'public');
                }
            }

            // If using supplier credit compensation
            if ($request->payment_method === 'supplier_credit' && $request->supplier_credit_id) {
                $credit = SupplierCredit::findOrFail($request->supplier_credit_id);
                if ($credit->remaining_amount < $amount) {
                    return response()->json(['message' => 'Saldo kredit retur tidak mencukupi.'], 422);
                }
                $credit->used_amount += $amount;
                $credit->remaining_amount = max(0, $credit->amount - $credit->used_amount);
                $credit->status = $credit->remaining_amount <= 0 ? 'used' : 'partial';
                $credit->save();
            }

            $paymentNumber = 'PAY-AP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

            $payment = PayablePayment::create([
                'payment_number' => $paymentNumber,
                'payable_id' => $payable->id,
                'payment_date' => $request->payment_date,
                'amount' => $amount,
                'payment_method' => $request->payment_method,
                'bank_name' => $request->bank_name,
                'bank_account_number' => $request->bank_account_number,
                'bank_account_name' => $request->bank_account_name,
                'reference_number' => $request->reference_number,
                'proof_file' => $proofPath ? '/storage/' . $proofPath : null,
                'supplier_credit_id' => $request->supplier_credit_id ?: null,
                'notes' => $request->notes,
                'created_by' => $user ? $user->id : null,
            ]);

            // Update Payable balances
            $payable->paid_amount = (float) $payable->paid_amount + $amount;
            $payable->remaining_amount = max(0, (float) $payable->total_amount - (float) $payable->paid_amount);

            if ($payable->remaining_amount <= 0) {
                $payable->status = 'paid';
            } else {
                $payable->status = 'partial';
            }
            $payable->save();

            DB::commit();

            return response()->json([
                'message' => 'Pembayaran hutang berhasil dicatat.',
                'payment' => $payment,
                'payable' => $payable->load('payments'),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal mencatat pembayaran: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Void a recorded payment.
     */
    public function voidPayment(Request $request, $payableId, $paymentId)
    {
        $payable = Payable::findOrFail($payableId);
        $payment = PayablePayment::where('payable_id', $payable->id)->findOrFail($paymentId);

        DB::beginTransaction();
        try {
            // Revert supplier credit if applicable
            if ($payment->payment_method === 'supplier_credit' && $payment->supplier_credit_id) {
                $credit = SupplierCredit::find($payment->supplier_credit_id);
                if ($credit) {
                    $credit->used_amount = max(0, $credit->used_amount - $payment->amount);
                    $credit->remaining_amount = $credit->amount - $credit->used_amount;
                    $credit->status = $credit->remaining_amount == $credit->amount ? 'available' : 'partial';
                    $credit->save();
                }
            }

            $amount = (float) $payment->amount;
            $payment->delete();

            $payable->paid_amount = max(0, (float) $payable->paid_amount - $amount);
            $payable->remaining_amount = (float) $payable->total_amount - (float) $payable->paid_amount;

            if ($payable->paid_amount <= 0) {
                $payable->status = 'unpaid';
            } else {
                $payable->status = 'partial';
            }
            $payable->save();

            DB::commit();

            return response()->json([
                'message' => 'Pembayaran hutang berhasil dibatalkan.',
                'payable' => $payable->load('payments'),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal membatalkan pembayaran: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Auto synchronizer helper to ensure every existing approved Goods Receipt creates a Payable record.
     */
    public function autoSyncFromGoodsReceipts()
    {
        $receipts = GoodsReceipt::with('purchaseOrder')
            ->where('total_amount', '>', 0)
            ->whereNotIn('id', Payable::whereNotNull('goods_receipt_id')->pluck('goods_receipt_id'))
            ->get();

        foreach ($receipts as $gr) {
            $po = $gr->purchaseOrder;
            $supplierId = $po ? $po->supplier_id : 1;
            $branchId = $po ? $po->branch_id : 1;
            $dueDate = $gr->due_date ?: ($po ? $po->due_date : null);
            $totalAmount = (float) $gr->total_amount;

            Payable::create([
                'payable_number' => 'AP-' . date('Ymd', strtotime($gr->created_at)) . '-' . rand(1000, 9999),
                'purchase_order_id' => $gr->purchase_order_id,
                'goods_receipt_id' => $gr->id,
                'supplier_id' => $supplierId,
                'branch_id' => $branchId,
                'invoice_number_supplier' => $gr->invoice_number_supplier ?: ($po ? $po->invoice_number_supplier : null),
                'invoice_date' => $gr->date ?: ($po ? $po->date : now()),
                'due_date' => $dueDate,
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'remaining_amount' => $totalAmount,
                'status' => 'unpaid',
                'notes' => $gr->notes ?: ($po ? $po->notes : null),
                'created_by' => $gr->user_id,
            ]);
        }
    }
}
