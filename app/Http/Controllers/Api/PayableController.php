<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payable;
use App\Models\PayableStatement;
use App\Models\PayablePayment;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierCredit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PayableController extends Controller
{
    /**
     * Display a listing of Monthly Accounts Payable Statements with financial KPI summary.
     */
    public function index(Request $request)
    {
        // Auto-sync approved Goods Receipts and group into Monthly Billing Statements
        $this->autoSyncStatements();

        $query = PayableStatement::with([
            'supplier',
            'branch',
            'payables.purchaseOrder',
            'payables.goodsReceipt',
            'payments.bankAccount',
            'payments.creator',
            'creator',
        ]);

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('period_month') && $request->period_month !== 'all') {
            $query->where('period_month', $request->period_month);
        }

        $today = now()->toDateString();

        if ($request->filled('status') && $request->status !== 'all') {
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
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('statement_number', 'like', "%{$search}%")
                         ->orWhere('period_month', 'like', "%{$search}%")
                         ->orWhereHas('supplier', function ($qSupplier) use ($search) {
                             $qSupplier->where('name', 'like', "%{$search}%")
                                       ->orWhere('phone', 'like', "%{$search}%");
                         })
                         ->orWhereHas('payables', function ($qPayable) use ($search) {
                             $qPayable->where('payable_number', 'like', "%{$search}%")
                                      ->orWhere('invoice_number_supplier', 'like', "%{$search}%");
                         });
            });
        }

        // Calculate summary KPIs across filtered set
        $summaryQuery = clone $query;
        $summaryQuery->reorder();
        $allStatements = $summaryQuery->get();

        $totalPayable = (float) $allStatements->sum('total_amount');
        $totalPaid = (float) $allStatements->sum('paid_amount');
        $totalRemaining = (float) $allStatements->sum('remaining_amount');

        $totalOverdue = (float) $allStatements->filter(function ($s) use ($today) {
            return $s->status !== 'paid' && $s->due_date && $s->due_date < $today;
        })->sum('remaining_amount');

        $dueSoon = (float) $allStatements->filter(function ($s) use ($today) {
            $next7Days = now()->addDays(7)->toDateString();
            return $s->status !== 'paid' && $s->due_date && $s->due_date >= $today && $s->due_date <= $next7Days;
        })->sum('remaining_amount');

        $summary = [
            'total_payable' => $totalPayable,
            'total_paid' => $totalPaid,
            'total_remaining' => $totalRemaining,
            'total_overdue' => $totalOverdue,
            'total_due_soon' => $dueSoon,
            'count_unpaid' => $allStatements->where('status', 'unpaid')->count(),
            'count_partial' => $allStatements->where('status', 'partial')->count(),
            'count_paid' => $allStatements->where('status', 'paid')->count(),
            'count_overdue' => $allStatements->filter(function ($s) use ($today) {
                return $s->status !== 'paid' && $s->due_date && $s->due_date < $today;
            })->count(),
            'count_total' => $allStatements->count(),
        ];

        // Available billing cycle periods for selector
        $availablePeriods = PayableStatement::select('period_month', 'period_start_date', 'period_end_date', 'due_date')
            ->distinct()
            ->orderBy('period_month', 'desc')
            ->get()
            ->map(function ($p) {
                $monthDate = Carbon::parse($p->period_month . '-01');
                $startFormatted = Carbon::parse($p->period_start_date)->translatedFormat('d M');
                $endFormatted = Carbon::parse($p->period_end_date)->translatedFormat('d M Y');
                return [
                    'period_month' => $p->period_month,
                    'label' => $monthDate->translatedFormat('F Y') . " ({$startFormatted} - {$endFormatted})",
                    'start_date' => $p->period_start_date,
                    'end_date' => $p->period_end_date,
                    'due_date' => $p->due_date,
                ];
            });

        $itemsPerPage = (int) $request->input('itemsPerPage', 15);
        $query->orderBy('period_month', 'desc')->orderBy('due_date', 'asc');

        if ($itemsPerPage === -1) {
            $statements = $query->get();
            $paginated = null;
        } else {
            $paginated = $query->paginate($itemsPerPage);
            $statements = $paginated->items();
        }

        $response = [
            'data' => $statements,
            'summary' => $summary,
            'available_periods' => $availablePeriods,
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
     * Display the specified Monthly Billing Statement with detail bundled invoices & item-level payment tracking.
     */
    public function show($id)
    {
        $statement = PayableStatement::with([
            'supplier',
            'branch.owner',
            'payables.purchaseOrder.items.product',
            'payables.goodsReceipt.purchaseOrder',
            'payables.goodsReceipt.items.productBranch.product',
            'payables.goodsReceipt.items.purchaseOrderItem.product',
            'payables.goodsReceipt.items.paymentAllocations',
            'payments.paymentItems.goodsReceiptItem',
            'payments.bankAccount',
            'payments.creator',
            'payments.user',
            'payments.supplierCredit',
            'creator',
        ])->findOrFail($id);

        $statement->recalculateTotals();

        // Calculate and append item-level details
        foreach ($statement->payables as $payable) {
            $po = $payable->purchaseOrder ?: ($payable->goodsReceipt ? $payable->goodsReceipt->purchaseOrder : null);
            $poNumber = $po ? $po->po_number : '-';

            if (!$payable->purchase_order_id && $po) {
                $payable->purchase_order_id = $po->id;
                $payable->saveQuietly();
            }

            if ($payable->goodsReceipt && $payable->goodsReceipt->items) {
                foreach ($payable->goodsReceipt->items as $item) {
                    $product = null;
                    if ($item->productBranch && $item->productBranch->product) {
                        $product = $item->productBranch->product;
                    } elseif ($item->purchaseOrderItem && $item->purchaseOrderItem->product) {
                        $product = $item->purchaseOrderItem->product;
                    } elseif ($po && $po->items) {
                        $matchingPoItem = $po->items->firstWhere('id', $item->purchase_order_item_id);
                        if ($matchingPoItem && $matchingPoItem->product) {
                            $product = $matchingPoItem->product;
                        }
                    }

                    $item->product_name = $product ? $product->name : ($item->name ?: 'Produk #' . $item->id);
                    $item->sku = $product ? $product->sku : '-';
                    $item->po_number = $poNumber;

                    $qty = (float) ($item->qty_received ?: ($item->qty ?: 1));
                    $unitPrice = (float) ($item->net_unit_price ?: ($item->unit_cost ?: ($item->gross_price ?: 0)));
                    $subtotal = $qty * $unitPrice;
                    if ($subtotal <= 0 && $item->total_price > 0) {
                        $subtotal = (float) $item->total_price;
                    }

                    $paid = (float) $item->paid_amount;
                    if ($paid <= 0 && $item->paymentAllocations) {
                        $paid = (float) $item->paymentAllocations->sum('allocated_amount');
                    }

                    $remaining = max(0, $subtotal - $paid);
                    $status = 'unpaid';
                    if ($remaining <= 0 && $subtotal > 0) {
                        $status = 'paid';
                    } elseif ($paid > 0) {
                        $status = 'partial';
                    }

                    $item->calculated_subtotal = $subtotal;
                    $item->paid_amount = $paid;
                    $item->remaining_amount = $remaining;
                    $item->payment_status = $status;
                }
            }
        }

        return response()->json($statement);
    }

    /**
     * Record an installment, full payment, or selected items payment towards the Monthly Billing Statement.
     */
    public function recordPayment(Request $request, $id)
    {
        $user = $request->user() ?: (auth('sanctum')->user() ?: (auth()->user() ?: \App\Models\User::first()));
        $statement = PayableStatement::findOrFail($id);

        $request->validate([
            'amount' => 'required|numeric|min:1|max:' . ($statement->remaining_amount + 0.01),
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:bank_transfer,cash,giro_cheque,supplier_credit',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:100',
            'bank_account_name' => 'nullable|string|max:100',
            'reference_number' => 'nullable|string|max:100',
            'proof_file' => 'nullable',
            'supplier_credit_id' => 'nullable|exists:supplier_credits,id',
            'selected_item_ids' => 'nullable|array',
            'selected_item_ids.*' => 'integer',
            'notes' => 'nullable|string',
        ]);

        $amount = (float) $request->amount;
        if ($amount <= 0) {
            return response()->json(['message' => 'Nominal pembayaran harus lebih besar dari 0.'], 422);
        }

        if ($amount > ((float) $statement->remaining_amount + 0.01)) {
            return response()->json(['message' => 'Nominal pembayaran melebihi sisa tagihan periode (Rp ' . number_format($statement->remaining_amount, 0, ',', '.') . ').'], 422);
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

            $bankAccount = null;
            if ($request->payment_method === 'bank_transfer' && $request->bank_account_id) {
                $bankAccount = \App\Models\BankAccount::findOrFail($request->bank_account_id);
                if ((float) $bankAccount->current_balance < $amount) {
                    return response()->json([
                        'message' => "Saldo rekening {$bankAccount->bank_name} ({$bankAccount->account_number}) tidak mencukupi. Tersedia: Rp " . number_format($bankAccount->current_balance, 0, ',', '.') . ", dibutuhkan: Rp " . number_format($amount, 0, ',', '.') . "."
                    ], 422);
                }
                // Potong saldo bank rekening
                $bankAccount->decrement('current_balance', $amount);
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
                'payable_statement_id' => $statement->id,
                'payable_id' => null,
                'payment_date' => $request->payment_date,
                'amount' => $amount,
                'payment_method' => $request->payment_method,
                'bank_account_id' => $bankAccount ? $bankAccount->id : null,
                'bank_name' => $bankAccount ? $bankAccount->bank_name : $request->bank_name,
                'bank_account_number' => $bankAccount ? $bankAccount->account_number : $request->bank_account_number,
                'bank_account_name' => $bankAccount ? $bankAccount->account_name : $request->bank_account_name,
                'reference_number' => $request->reference_number,
                'proof_file' => $proofPath ? '/storage/' . $proofPath : null,
                'supplier_credit_id' => $request->supplier_credit_id ?: null,
                'notes' => $request->notes,
                'created_by' => $user ? $user->id : 1,
            ]);

            // Item-level allocation
            $selectedItemIds = $request->input('selected_item_ids', []);
            $remainingPayment = $amount;

            // 1. If user checked specific items
            if (!empty($selectedItemIds)) {
                $items = \App\Models\GoodsReceiptItem::whereIn('id', $selectedItemIds)->get();
                foreach ($items as $item) {
                    if ($remainingPayment <= 0) break;

                    $qty = (float) ($item->qty_received ?: ($item->qty ?: 1));
                    $unitPrice = (float) ($item->net_unit_price ?: ($item->unit_cost ?: ($item->gross_price ?: 0)));
                    $itemSubtotal = $qty * $unitPrice;
                    $itemRemaining = max(0, $itemSubtotal - (float) $item->paid_amount);

                    $alloc = min($remainingPayment, $itemRemaining > 0 ? $itemRemaining : $remainingPayment);
                    if ($alloc > 0) {
                        \App\Models\PayablePaymentItem::create([
                            'payable_payment_id' => $payment->id,
                            'goods_receipt_item_id' => $item->id,
                            'payable_id' => null,
                            'allocated_amount' => $alloc,
                        ]);

                        $item->paid_amount = (float) $item->paid_amount + $alloc;
                        $item->remaining_amount = max(0, $itemSubtotal - $item->paid_amount);
                        $item->payment_status = $item->remaining_amount <= 0 ? 'paid' : 'partial';
                        $item->save();

                        $remainingPayment -= $alloc;
                    }
                }
            } else {
                // 2. FIFO allocation across all goods receipt items in this statement
                $statementPayables = \App\Models\Payable::where('payable_statement_id', $statement->id)
                    ->with('goodsReceipt.items')
                    ->orderBy('invoice_date', 'asc')
                    ->get();

                foreach ($statementPayables as $payable) {
                    if ($remainingPayment <= 0) break;

                    if ($payable->goodsReceipt && $payable->goodsReceipt->items) {
                        foreach ($payable->goodsReceipt->items as $item) {
                            if ($remainingPayment <= 0) break;

                            $qty = (float) ($item->qty_received ?: ($item->qty ?: 1));
                            $unitPrice = (float) ($item->net_unit_price ?: ($item->unit_cost ?: ($item->gross_price ?: 0)));
                            $itemSubtotal = $qty * $unitPrice;
                            $itemRemaining = max(0, $itemSubtotal - (float) $item->paid_amount);

                            if ($itemRemaining > 0) {
                                $alloc = min($remainingPayment, $itemRemaining);
                                \App\Models\PayablePaymentItem::create([
                                    'payable_payment_id' => $payment->id,
                                    'goods_receipt_item_id' => $item->id,
                                    'payable_id' => $payable->id,
                                    'allocated_amount' => $alloc,
                                ]);

                                $item->paid_amount = (float) $item->paid_amount + $alloc;
                                $item->remaining_amount = max(0, $itemSubtotal - $item->paid_amount);
                                $item->payment_status = $item->remaining_amount <= 0 ? 'paid' : 'partial';
                                $item->save();

                                $remainingPayment -= $alloc;
                            }
                        }
                    }
                }
            }

            // Recalculate Statement balances and status
            $statement->recalculateTotals();

            DB::commit();

            // Auto-journal in accounting
            try {
                \App\Services\JournalService::journalForPayablePayment($payment);
            } catch (\Exception $jEx) {
                \Illuminate\Support\Facades\Log::warning('Auto-journal PayablePayment failed: ' . $jEx->getMessage());
            }

            return response()->json([
                'message' => 'Pembayaran tagihan bulanan dan barang berhasil dicatat.',
                'payment' => $payment,
                'statement' => $statement->load(['payments.creator', 'payables']),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal mencatat pembayaran: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Void a recorded payment on a statement.
     */
    public function voidPayment(Request $request, $id, $paymentId)
    {
        $statement = PayableStatement::findOrFail($id);
        $payment = PayablePayment::where('payable_statement_id', $statement->id)->findOrFail($paymentId);

        DB::beginTransaction();
        try {
            // Revert bank account balance if paid via bank
            if ($payment->bank_account_id && $payment->amount > 0) {
                $bankAccount = \App\Models\BankAccount::find($payment->bank_account_id);
                if ($bankAccount) {
                    $bankAccount->increment('current_balance', $payment->amount);
                }
            }

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

            // Revert item-level allocations
            $paymentItems = \App\Models\PayablePaymentItem::where('payable_payment_id', $payment->id)->get();
            foreach ($paymentItems as $pi) {
                if ($pi->goods_receipt_item_id) {
                    $item = \App\Models\GoodsReceiptItem::find($pi->goods_receipt_item_id);
                    if ($item) {
                        $qty = (float) ($item->qty_received ?: ($item->qty ?: 1));
                        $unitPrice = (float) ($item->net_unit_price ?: ($item->unit_cost ?: ($item->gross_price ?: 0)));
                        $itemSubtotal = $qty * $unitPrice;

                        $item->paid_amount = max(0, (float) $item->paid_amount - (float) $pi->allocated_amount);
                        $item->remaining_amount = max(0, $itemSubtotal - $item->paid_amount);
                        $item->payment_status = $item->paid_amount <= 0 ? 'unpaid' : ($item->remaining_amount <= 0 ? 'paid' : 'partial');
                        $item->save();
                    }
                }
            }

            $payment->delete();

            $statement->recalculateTotals();

            DB::commit();

            return response()->json([
                'message' => 'Pembayaran tagihan bulanan berhasil dibatalkan dan saldo bank dikembalikan.',
                'statement' => $statement->load(['payments.bankAccount', 'payments.creator']),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal membatalkan pembayaran: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display all individual invoice transactions (kuitansi / GR) with their associated statement.
     */
    public function invoices(Request $request)
    {
        $query = Payable::with([
            'supplier',
            'branch',
            'purchaseOrder',
            'goodsReceipt.items.productBranch.product',
            'payableStatement',
        ]);

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('payable_statement_id')) {
            $query->where('payable_statement_id', $request->payable_statement_id);
        }

        $search = $request->query('search', $request->query('q'));
        if (!empty($search)) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('payable_number', 'like', "%{$search}%")
                         ->orWhere('invoice_number_supplier', 'like', "%{$search}%")
                         ->orWhereHas('supplier', function ($qSupplier) use ($search) {
                             $qSupplier->where('name', 'like', "%{$search}%");
                         })
                         ->orWhereHas('purchaseOrder', function ($qPo) use ($search) {
                             $qPo->where('po_number', 'like', "%{$search}%");
                         });
            });
        }

        $itemsPerPage = (int) $request->input('itemsPerPage', 15);
        $query->orderBy('invoice_date', 'desc')->orderBy('id', 'desc');

        if ($itemsPerPage === -1) {
            $invoices = $query->get();
            $paginated = null;
        } else {
            $paginated = $query->paginate($itemsPerPage);
            $invoices = $paginated->items();
        }

        $response = [
            'data' => $invoices,
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
     * Return list of available billing cycle periods.
     */
    public function periods()
    {
        $periods = PayableStatement::select('period_month', 'period_start_date', 'period_end_date', 'due_date')
            ->distinct()
            ->orderBy('period_month', 'desc')
            ->get()
            ->map(function ($p) {
                $monthDate = Carbon::parse($p->period_month . '-01');
                $startFormatted = Carbon::parse($p->period_start_date)->translatedFormat('d M');
                $endFormatted = Carbon::parse($p->period_end_date)->translatedFormat('d M Y');
                return [
                    'period_month' => $p->period_month,
                    'label' => $monthDate->translatedFormat('F Y') . " ({$startFormatted} - {$endFormatted})",
                    'start_date' => $p->period_start_date,
                    'end_date' => $p->period_end_date,
                    'due_date' => $p->due_date,
                ];
            });

        return response()->json($periods);
    }

    /**
     * Calculate billing cycle cutoff (Cycle: 26th previous month to 25th current month).
     */
    public static function calculateBillingCycle($dateInput, $cutoffDay = 25)
    {
        $date = Carbon::parse($dateInput ?: now());
        $day = $date->day;
        $year = $date->year;
        $month = $date->month;

        if ($day <= $cutoffDay) {
            // Falls in cycle month M (e.g. 2026-08-10 -> cycle 2026-08)
            $cycleYear = $year;
            $cycleMonth = $month;
        } else {
            // Falls in next cycle month M+1 (e.g. 2026-07-26 -> cycle 2026-08)
            $nextMonthDate = (clone $date)->addMonthNoOverflow();
            $cycleYear = $nextMonthDate->year;
            $cycleMonth = $nextMonthDate->month;
        }

        $periodMonth = sprintf('%04d-%02d', $cycleYear, $cycleMonth);

        // Period End Date: cutoffDay of cycleMonth (e.g. 2026-08-25)
        $cycleEnd = Carbon::create($cycleYear, $cycleMonth, min($cutoffDay, 28))->toDateString();
        // Period Start Date: cutoffDay + 1 of previous month (e.g. 2026-07-26)
        $cycleStart = Carbon::create($cycleYear, $cycleMonth, min($cutoffDay, 28))->subMonthNoOverflow()->addDay()->toDateString();
        // Payment Due Date: cutoffDay of next month (e.g. 2026-09-25)
        $dueDate = Carbon::create($cycleYear, $cycleMonth, min($cutoffDay, 28))->addMonthNoOverflow()->toDateString();

        return [
            'period_month' => $periodMonth,
            'period_year' => $cycleYear,
            'cutoff_day' => $cutoffDay,
            'period_start_date' => $cycleStart,
            'period_end_date' => $cycleEnd,
            'due_date' => $dueDate,
        ];
    }

    /**
     * Find or create the corresponding PayableStatement for an invoice date.
     */
    public static function getOrCreateStatementForSupplier($supplierId, $branchId, $invoiceDate, $userId = null)
    {
        $supplier = Supplier::find($supplierId);
        $cutoffDay = $supplier && $supplier->cutoff_day ? (int) $supplier->cutoff_day : 25;

        $cycle = self::calculateBillingCycle($invoiceDate, $cutoffDay);

        $statement = PayableStatement::firstOrCreate(
            [
                'supplier_id' => $supplierId,
                'period_month' => $cycle['period_month'],
                'branch_id' => $branchId,
            ],
            [
                'statement_number' => 'BILL-' . str_replace('-', '', $cycle['period_month']) . '-S' . $supplierId . '-' . ($branchId ?: 'ALL'),
                'period_year' => $cycle['period_year'],
                'cutoff_day' => $cycle['cutoff_day'],
                'period_start_date' => $cycle['period_start_date'],
                'period_end_date' => $cycle['period_end_date'],
                'due_date' => $cycle['due_date'],
                'total_invoices_count' => 0,
                'total_purchases_amount' => 0,
                'total_returns_deduction' => 0,
                'total_amount' => 0,
                'paid_amount' => 0,
                'remaining_amount' => 0,
                'status' => 'unpaid',
                'created_by' => $userId,
            ]
        );

        return $statement;
    }

    /**
     * Auto synchronizer helper to ensure every Goods Receipt has a Payable and is linked to its Billing Statement.
     */
    public function autoSyncStatements()
    {
        // 1. Ensure all GoodsReceipts with total_amount > 0 have Payables
        $receipts = GoodsReceipt::with('purchaseOrder')
            ->where('total_amount', '>', 0)
            ->whereNotIn('id', Payable::whereNotNull('goods_receipt_id')->pluck('goods_receipt_id'))
            ->get();

        foreach ($receipts as $gr) {
            $po = $gr->purchaseOrder;
            $supplierId = $po ? $po->supplier_id : 1;
            $branchId = $po ? $po->branch_id : 1;
            $invoiceDate = $gr->date ?: ($po ? $po->date : ($gr->created_at ? $gr->created_at->toDateString() : now()->toDateString()));
            $dueDate = $gr->due_date ?: ($po ? $po->due_date : null);
            $totalAmount = (float) $gr->total_amount;

            $payable = Payable::create([
                'payable_number' => 'AP-' . date('Ymd', strtotime($invoiceDate)) . '-' . rand(1000, 9999),
                'purchase_order_id' => $gr->purchase_order_id,
                'goods_receipt_id' => $gr->id,
                'supplier_id' => $supplierId,
                'branch_id' => $branchId,
                'invoice_number_supplier' => $gr->invoice_number_supplier ?: ($po ? $po->invoice_number_supplier : null),
                'invoice_date' => $invoiceDate,
                'due_date' => $dueDate,
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'remaining_amount' => $totalAmount,
                'status' => 'unpaid',
                'notes' => $gr->notes ?: ($po ? $po->notes : null),
                'created_by' => $gr->user_id,
            ]);
        }

        // 2. Link all unassigned Payables to their respective PayableStatement & backfill purchase_order_id
        $unlinkedPayables = Payable::with('goodsReceipt')->where(function($q) {
            $q->whereNull('payable_statement_id')->orWhereNull('purchase_order_id');
        })->get();
        $affectedStatementIds = [];

        foreach ($unlinkedPayables as $payable) {
            $updates = [];
            if (!$payable->purchase_order_id && $payable->goodsReceipt && $payable->goodsReceipt->purchase_order_id) {
                $updates['purchase_order_id'] = $payable->goodsReceipt->purchase_order_id;
            }
            if (!$payable->payable_statement_id) {
                $invoiceDate = $payable->invoice_date ?: ($payable->created_at ? $payable->created_at->toDateString() : now()->toDateString());
                $statement = self::getOrCreateStatementForSupplier($payable->supplier_id, $payable->branch_id, $invoiceDate, $payable->created_by);
                $updates['payable_statement_id'] = $statement->id;
                $affectedStatementIds[$statement->id] = true;
            }
            if (!empty($updates)) {
                $payable->update($updates);
            }
        }

        // 3. Migrate any legacy payments that had payable_id to payable_statement_id if needed
        $legacyPayments = PayablePayment::whereNull('payable_statement_id')->whereNotNull('payable_id')->get();
        foreach ($legacyPayments as $lp) {
            $parentPayable = Payable::find($lp->payable_id);
            if ($parentPayable && $parentPayable->payable_statement_id) {
                $lp->update(['payable_statement_id' => $parentPayable->payable_statement_id]);
                $affectedStatementIds[$parentPayable->payable_statement_id] = true;
            }
        }

        // 4. Recalculate totals for all statements
        $allStatements = PayableStatement::all();
        foreach ($allStatements as $stmt) {
            $stmt->recalculateTotals();
        }
    }
}
