<?php

namespace App\Http\Controllers;

use App\Models\Receivable;
use App\Models\ReceivablePayment;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReceivableController extends Controller
{
    public function index(Request $request)
    {
        $query = Receivable::with(['customer', 'sale.branch.owner', 'sale.user']);

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
        $receivable->load(['customer', 'sale.branch.owner', 'sale.items.productBranch.product', 'payments.user', 'payments.bankAccount']);
        return response()->json($receivable);
    }

    public function pay(Request $request, Receivable $receivable)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string|in:cash,transfer,bank_transfer,qris,giro_cheque,other',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'payment_date' => 'required|date',
            'payment_proof' => 'nullable|image|max:5120',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:100',
            'bank_account_name' => 'nullable|string|max:100',
            'transfer_phone_number' => 'nullable|string|max:100',
        ]);

        if ($receivable->status === 'paid') {
            return response()->json(['message' => 'Piutang ini sudah lunas seluruhnya.'], 400);
        }

        $remainingBalance = $receivable->amount_due - $receivable->amount_paid;
        
        if ($validated['amount'] > ($remainingBalance + 0.01)) {
            return response()->json([
                'message' => 'Nominal pembayaran tidak boleh melebihi sisa piutang.',
                'remaining' => $remainingBalance
            ], 422);
        }

        $user = $request->user() ?: (auth('sanctum')->user() ?: (auth()->user() ?: \App\Models\User::first()));

        DB::beginTransaction();
        try {
            $proofPath = null;
            if ($request->hasFile('payment_proof')) {
                $proofPath = $request->file('payment_proof')->store('payment_proofs', 'public');
            }

            // Jika pembayaran non-tunai (Transfer Bank / QRIS), update & tambah saldo rekening bank
            $bankAccount = null;
            if ($request->filled('bank_account_id')) {
                $bankAccount = BankAccount::find($request->bank_account_id);
                if ($bankAccount) {
                    $bankAccount->increment('current_balance', $validated['amount']);
                }
            }

            // Create payment record
            $payment = ReceivablePayment::create([
                'receivable_id' => $receivable->id,
                'payment_date' => $validated['payment_date'],
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'bank_account_id' => $bankAccount ? $bankAccount->id : null,
                'payment_proof' => $proofPath ? '/storage/' . $proofPath : null,
                'bank_name' => $bankAccount ? $bankAccount->bank_name : ($validated['bank_name'] ?? null),
                'bank_account_number' => $bankAccount ? $bankAccount->account_number : ($validated['bank_account_number'] ?? null),
                'bank_account_name' => $bankAccount ? $bankAccount->account_name : ($validated['bank_account_name'] ?? null),
                'transfer_phone_number' => $validated['transfer_phone_number'] ?? null,
                'user_id' => $user ? $user->id : 1,
            ]);

            // Update receivable amounts and status
            $receivable->amount_paid += $validated['amount'];
            
            if ($receivable->amount_paid >= ($receivable->amount_due - 0.01)) {
                $receivable->status = 'paid';
            } else {
                $receivable->status = 'partial';
            }
            
            $receivable->save();

            DB::commit();

            // Auto-send Receipt Email jika pelanggan memiliki email
            try {
                \App\Services\EmailNotificationService::sendReceivableReceipt($payment, null, 'automatic', $user ? $user->id : 1);
            } catch (\Throwable $mailEx) {
                \Log::warning("Auto receipt email warning: " . $mailEx->getMessage());
            }

            return response()->json([
                'message' => 'Pembayaran piutang berhasil diproses dan saldo telah diperbarui.',
                'payment' => $payment->load(['user', 'bankAccount']),
                'receivable' => $receivable->load(['customer', 'sale.branch.owner', 'sale.items.productBranch.product', 'payments.user', 'payments.bankAccount'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal memproses pembayaran piutang: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Kirim manual surat tagihan invoice piutang ke email pelanggan.
     */
    public function sendEmail(Request $request, Receivable $receivable)
    {
        $request->validate([
            'email' => 'nullable|email',
        ]);

        $customEmail = $request->email;

        try {
            $log = \App\Services\EmailNotificationService::sendReceivableInvoice(
                $receivable,
                $customEmail,
                'manual',
                auth()->id()
            );

            if ($log->status === 'sent') {
                return response()->json([
                    'message' => 'Surat tagihan berhasil dikirim ke ' . $log->recipient_email,
                    'log' => $log,
                ]);
            } else {
                return response()->json([
                    'message' => 'Pengiriman email gagal: ' . ($log->error_message ?? 'Terjadi kesalahan SMTP'),
                    'log' => $log,
                ], 422);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Ambil histori log email untuk piutang ini.
     */
    public function emailLogs(Receivable $receivable)
    {
        $paymentIds = $receivable->payments()->pluck('id')->toArray();

        $logs = \App\Models\EmailLog::with(['user:id,name'])
            ->where(function($q) use ($receivable, $paymentIds) {
                $q->where(function($q1) use ($receivable) {
                    $q1->where('reference_type', Receivable::class)
                       ->where('reference_id', (string) $receivable->id);
                })->orWhere(function($q2) use ($paymentIds) {
                    $q2->where('reference_type', ReceivablePayment::class)
                       ->whereIn('reference_id', $paymentIds);
                });
            })
            ->latest()
            ->get();

        return response()->json(['data' => $logs]);
    }

    /**
     * Retry pengiriman email yang gagal.
     */
    public function retryEmail(Request $request, $id)
    {
        try {
            $log = \App\Services\EmailNotificationService::retry($id);

            return response()->json([
                'message' => 'Email berhasil dikirim ulang ke ' . $log->recipient_email,
                'log' => $log,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal mengirim ulang email: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function destroy(Request $request, Receivable $receivable)
    {
        // Delete Receivable will actually void the entire Sale transaction.
        $sale = $receivable->sale;
        
        if ($sale) {
            $saleController = new \App\Http\Controllers\Api\SaleController();
            return $saleController->destroy($request, $sale->id);
        }

        $receivable->delete();
        return response()->json(['message' => 'Receivable deleted successfully']);
    }
}
