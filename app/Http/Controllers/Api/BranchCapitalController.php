<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BranchCapital;
use App\Models\Branch;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class BranchCapitalController extends Controller
{
    /**
     * Get list of branch capital transactions
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $isGlobal = $user && ($user->can('manage all') || $user->can('Modal & ROI Cabang Approve') || $user->can('Modal & ROI Cabang Validate') || !$user->branch_id);

        $query = BranchCapital::with([
            'branch:id,name',
            'user:id,name',
            'approvedBy:id,name',
            'cashShift:id,opened_at,closed_at,actual_cash',
            'bankAccount:id,bank_name,account_number,account_name,type,current_balance',
        ]);

        // Branch isolation: if non-global user, scope to their branch
        if (!$isGlobal && $user && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($request->filled('branch_id') && $request->branch_id !== 'all') {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by type (injection / return)
        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        // Filter by status (approved / pending / rejected)
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        // Search keyword
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference_no', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhere('bank_name', 'like', "%{$search}%")
                  ->orWhere('account_name', 'like', "%{$search}%")
                  ->orWhereHas('branch', function ($b) use ($search) {
                      $b->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('bankAccount', function ($ba) use ($search) {
                      $ba->where('bank_name', 'like', "%{$search}%")
                        ->orWhere('account_number', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = (int) $request->input('per_page', 15);
        $transactions = $query->orderBy('date', 'desc')->orderBy('id', 'desc')->paginate($perPage);

        return response()->json($transactions);
    }

    /**
     * Get executive summary metrics (Total Injected, Total Returned, Remaining, Payback %)
     */
    public function summary(Request $request)
    {
        $user = $request->user();
        $isGlobal = $user && ($user->can('manage all') || $user->can('Modal & ROI Cabang Approve') || $user->can('Modal & ROI Cabang Validate') || !$user->branch_id);

        $baseQuery = BranchCapital::query();

        if (!$isGlobal && $user && $user->branch_id) {
            $baseQuery->where('branch_id', $user->branch_id);
            $branches = Branch::where('id', $user->branch_id)->select('id', 'name')->get();
        } elseif ($request->filled('branch_id') && $request->branch_id !== 'all') {
            $baseQuery->where('branch_id', $request->branch_id);
            $branches = Branch::where('id', $request->branch_id)->select('id', 'name')->get();
        } else {
            $branches = Branch::select('id', 'name')->get();
        }

        $totalInjected = (clone $baseQuery)
            ->where('type', 'injection')
            ->where('status', 'approved')
            ->sum('amount');

        $totalReturned = (clone $baseQuery)
            ->where('type', 'return')
            ->where('status', 'approved')
            ->sum('amount');

        $pendingReturned = (clone $baseQuery)
            ->where('type', 'return')
            ->where('status', 'pending')
            ->sum('amount');

        $remainingCapital = max(0, $totalInjected - $totalReturned);
        $paybackProgress = $totalInjected > 0 ? round(($totalReturned / $totalInjected) * 100, 1) : 0;

        // Breakdown per branch for consolidated overview
        $branches = Branch::select('id', 'name')->get();
        $branchBreakdown = $branches->map(function ($b) {
            $injected = BranchCapital::where('branch_id', $b->id)
                ->where('type', 'injection')
                ->where('status', 'approved')
                ->sum('amount');

            $returned = BranchCapital::where('branch_id', $b->id)
                ->where('type', 'return')
                ->where('status', 'approved')
                ->sum('amount');

            $pending = BranchCapital::where('branch_id', $b->id)
                ->where('type', 'return')
                ->where('status', 'pending')
                ->sum('amount');

            $remaining = max(0, $injected - $returned);
            $progress = $injected > 0 ? round(($returned / $injected) * 100, 1) : 0;

            return [
                'branch_id' => $b->id,
                'branch_name' => $b->name,
                'total_injected' => (float) $injected,
                'total_returned' => (float) $returned,
                'pending_returned' => (float) $pending,
                'remaining_capital' => (float) $remaining,
                'payback_percentage' => min(100, $progress),
            ];
        });

        return response()->json([
            'total_injected' => (float) $totalInjected,
            'total_returned' => (float) $totalReturned,
            'pending_returned' => (float) $pendingReturned,
            'remaining_capital' => (float) $remainingCapital,
            'payback_percentage' => min(100, $paybackProgress),
            'branch_breakdown' => $branchBreakdown,
        ]);
    }

    /**
     * Store a new capital transaction (Injection or Return)
     */
    public function store(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'type' => 'required|in:injection,return',
            'category' => 'required|string|max:100',
            'amount' => 'required|numeric|min:1',
            'date' => 'required|date',
            'payment_method' => 'required|string|max:50',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'bank_name' => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:50',
            'account_name' => 'nullable|string|max:100',
            'proof_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf,webp|max:5120',
            'notes' => 'nullable|string|max:1000',
            'cash_shift_id' => 'nullable|exists:cash_shifts,id',
        ]);

        $prefix = $request->type === 'injection' ? 'INJ' : 'RET';
        $referenceNo = 'CAP-' . $prefix . '-' . date('Ym') . '-' . strtoupper(substr(uniqid(), -5));

        $proofPath = null;
        if ($request->hasFile('proof_file')) {
            $proofPath = $request->file('proof_file')->store('branch_capitals', 'public');
        }

        $user = $request->user();
        
        // If type is injection, check if it is a request from branch or direct injection from owner
        $isRequest = $request->boolean('is_request') || $request->status === 'pending';
        $status = ($request->type === 'injection' && !$isRequest) ? 'approved' : 'pending';
        $approvedBy = $status === 'approved' ? $user->id : null;
        $approvedAt = $status === 'approved' ? Carbon::now() : null;

        // Auto-fill bank details if bank_account_id is provided
        $bankAccountId = $request->bank_account_id;
        $bankName = $request->bank_name;
        $accountNumber = $request->account_number;
        $accountName = $request->account_name;

        if ($bankAccountId) {
            $bank = \App\Models\BankAccount::find($bankAccountId);
            if ($bank) {
                $bankName = $bankName ?: $bank->bank_name;
                $accountNumber = $accountNumber ?: $bank->account_number;
                $accountName = $accountName ?: $bank->account_name;
            }
        }

        $capital = BranchCapital::create([
            'reference_no' => $referenceNo,
            'branch_id' => $request->branch_id,
            'cash_shift_id' => $request->cash_shift_id,
            'user_id' => $user->id,
            'type' => $request->type,
            'category' => $request->category,
            'amount' => $request->amount,
            'date' => $request->date,
            'payment_method' => $request->payment_method,
            'bank_account_id' => $bankAccountId,
            'bank_name' => $bankName,
            'account_number' => $accountNumber,
            'account_name' => $accountName,
            'proof_file' => $proofPath,
            'notes' => $request->notes,
            'status' => $status,
            'approved_by' => $approvedBy,
            'approved_at' => $approvedAt,
        ]);

        // If direct injection from owner via Bank is approved, deduct owner's bank account
        $isBank = in_array(strtolower($capital->payment_method ?? ''), ['transfer', 'transfer bank', 'bank_transfer', 'qris']);
        if ($capital->type === 'injection' && $status === 'approved' && $isBank) {
            $bank = null;
            if ($capital->bank_account_id) {
                $bank = \App\Models\BankAccount::find($capital->bank_account_id);
            } elseif ($capital->bank_name) {
                $bank = \App\Models\BankAccount::where('bank_name', $capital->bank_name)->where('is_active', true)->first();
            }
            if ($bank) {
                $bank->decrement('current_balance', $capital->amount);
            }
        }

        $branch = Branch::find($request->branch_id);
        $branchName = $branch ? $branch->name : 'Cabang';
        $formattedAmount = 'Rp ' . number_format($capital->amount, 0, ',', '.');

        if ($capital->type === 'injection' && $status === 'approved') {
            NotificationService::notifyBranch(
                $capital->branch_id,
                'Injeksi Modal Baru',
                "Owner telah menambahkan modal sebesar {$formattedAmount} ke cabang Anda.",
                '/apps/branch-capitals',
                'success',
                'ri-hand-coin-line'
            );

            NotificationService::notifyUser(
                $user->id,
                'Injeksi Modal Berhasil Dicatat',
                "Injeksi modal sebesar {$formattedAmount} ke cabang {$branchName} berhasil disalurkan & dicatat.",
                '/apps/branch-capitals',
                'success',
                'ri-hand-coin-line',
                $capital->branch_id
            );
        } elseif ($capital->type === 'injection' && $status === 'pending') {
            NotificationService::notifyOwnerAndAdmins(
                'Permohonan Modal Tambahan',
                "Cabang {$branchName} mengajukan permohonan modal {$formattedAmount} disertai dokumen proposal.",
                '/apps/branch-capitals',
                'warning',
                'ri-file-text-line',
                $capital->branch_id
            );
        } elseif ($capital->type === 'return') {
            NotificationService::notifyOwnerAndAdmins(
                'Setoran Cicilan Modal Masuk',
                "Cabang {$branchName} menyetorkan cicilan pengembalian modal sebesar {$formattedAmount}.",
                '/apps/branch-capitals',
                'success',
                'ri-bank-card-line',
                $capital->branch_id
            );

            // Auto-send Email Notifikasi Setoran ke Owner
            try {
                \App\Services\EmailNotificationService::sendCapitalInstallmentAlert($capital, null, 'automatic', $user->id ?? null);
            } catch (\Throwable $mailEx) {
                \Log::warning("Auto capital return email warning: " . $mailEx->getMessage());
            }
        }

        $message = $status === 'approved' 
            ? 'Penyertaan modal berhasil dicatat dan disetujui.' 
            : ($request->type === 'injection' 
                ? 'Permintaan modal tambahan berhasil diajukan dan menunggu persetujuan Owner.' 
                : 'Pengajuan setoran pengembalian modal berhasil dikirim dan menunggu persetujuan Owner.');

        return response()->json([
            'message' => $message,
            'capital' => $capital->load(['branch:id,name', 'user:id,name', 'bankAccount:id,bank_name,account_number,account_name']),
        ], 201);
    }

    /**
     * Approve a capital transaction (Return or Injection Request)
     */
    public function approve(Request $request, $id)
    {
        $capital = BranchCapital::findOrFail($id);
        
        $dataToUpdate = [
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => Carbon::now(),
        ];

        if ($request->filled('payment_method')) {
            $dataToUpdate['payment_method'] = $request->payment_method;
        }
        if ($request->filled('bank_account_id')) {
            $dataToUpdate['bank_account_id'] = $request->bank_account_id;
            $b = \App\Models\BankAccount::find($request->bank_account_id);
            if ($b) {
                $dataToUpdate['bank_name'] = $b->bank_name;
                $dataToUpdate['account_number'] = $b->account_number;
                $dataToUpdate['account_name'] = $b->account_name;
            }
        } elseif ($request->filled('bank_name')) {
            $dataToUpdate['bank_name'] = $request->bank_name;
            if ($request->filled('account_number')) $dataToUpdate['account_number'] = $request->account_number;
            if ($request->filled('account_name')) $dataToUpdate['account_name'] = $request->account_name;
        }

        if ($request->hasFile('proof_file')) {
            if ($capital->proof_file) {
                Storage::disk('public')->delete($capital->proof_file);
            }
            $dataToUpdate['proof_file'] = $request->file('proof_file')->store('branch_capitals', 'public');
        }

        $capital->update($dataToUpdate);

        $isBank = in_array(strtolower($capital->payment_method ?? ''), ['transfer', 'transfer bank', 'bank_transfer', 'qris']);
        $isCash = in_array(strtolower($capital->payment_method ?? ''), ['cash', 'tunai', 'kas']);

        if ($capital->type === 'injection') {
            // Injeksi modal disetujui: jika via Bank, potong saldo rekening bank Owner
            if ($isBank || $capital->bank_account_id) {
                $bank = null;
                if ($capital->bank_account_id) {
                    $bank = \App\Models\BankAccount::find($capital->bank_account_id);
                } elseif ($capital->bank_name) {
                    $bank = \App\Models\BankAccount::where('bank_name', $capital->bank_name)->where('is_active', true)->first();
                }
                if ($bank) {
                    $bank->decrement('current_balance', $capital->amount);
                }
            }
        } else {
            // Pengembalian modal (return) disetujui:
            if ($isBank || $capital->bank_account_id) {
                // Via Bank: saldo bank Owner bertambah
                $bank = null;
                if ($capital->bank_account_id) {
                    $bank = \App\Models\BankAccount::find($capital->bank_account_id);
                } elseif ($capital->bank_name) {
                    $bank = \App\Models\BankAccount::where('bank_name', $capital->bank_name)->where('is_active', true)->first();
                }
                if (!$bank) {
                    $bank = \App\Models\BankAccount::where('is_default', true)->where('is_active', true)->first();
                }
                if ($bank) {
                    $bank->increment('current_balance', $capital->amount);
                }
            } elseif ($isCash) {
                // Via Tunai: potong kas kecil cabang otomatis
                try {
                    \App\Models\PettyCash::create([
                        'branch_id' => $capital->branch_id,
                        'user_id' => $capital->user_id,
                        'cash_shift_id' => $capital->cash_shift_id,
                        'category' => 'Pengembalian Modal / ROI',
                        'payment_method' => 'cash',
                        'amount' => $capital->amount,
                        'description' => 'Setoran Pengembalian Modal / ROI ke Owner (' . $capital->reference_no . ')',
                        'date' => $capital->date ?: Carbon::now()->toDateString(),
                    ]);
                } catch (\Throwable $pettyEx) {
                    \Log::warning("Auto petty cash deduction warning: " . $pettyEx->getMessage());
                }
            }
        }

        $branch = $capital->branch;
        $branchName = $branch ? $branch->name : 'Cabang';
        $formattedAmount = 'Rp ' . number_format($capital->amount, 0, ',', '.');

        if ($capital->type === 'injection') {
            NotificationService::notifyUser(
                $capital->user_id,
                'Permohonan Modal Disetujui',
                "Permohonan modal {$formattedAmount} untuk cabang {$branchName} telah disetujui & disalurkan oleh Owner.",
                '/apps/branch-capitals',
                'success',
                'ri-checkbox-circle-line'
            );
            NotificationService::notifyBranch(
                $capital->branch_id,
                'Penyaluran Modal Disetujui',
                "Dana modal sebesar {$formattedAmount} telah disalurkan oleh Owner ke cabang Anda.",
                '/apps/branch-capitals',
                'success',
                'ri-hand-coin-line'
            );
        } else {
            NotificationService::notifyUser(
                $capital->user_id,
                'Setoran Modal Diterima',
                "Setoran cicilan modal sebesar {$formattedAmount} telah disetujui dan diverifikasi oleh Owner.",
                '/apps/branch-capitals',
                'success',
                'ri-checkbox-circle-line'
            );
        }

        $msg = $capital->type === 'injection' 
            ? 'Permintaan modal tambahan telah disetujui & disalurkan.' 
            : 'Setoran pengembalian modal telah disetujui.';

        return response()->json([
            'message' => $msg,
            'capital' => $capital->load(['branch:id,name', 'approvedBy:id,name', 'bankAccount:id,bank_name,account_number,account_name']),
        ]);
    }

    /**
     * Reject a capital transaction
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $capital = BranchCapital::findOrFail($id);
        
        $capital->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
            'approved_at' => Carbon::now(),
            'notes' => $capital->notes . ($request->reason ? "\n[Alasan Penolakan: " . $request->reason . "]" : ""),
        ]);

        $formattedAmount = 'Rp ' . number_format($capital->amount, 0, ',', '.');
        NotificationService::notifyUser(
            $capital->user_id,
            'Pengajuan Modal Ditolak',
            "Pengajuan modal {$formattedAmount} ditolak oleh Owner. Alasan: " . $request->reason,
            '/apps/branch-capitals',
            'error',
            'ri-close-circle-line'
        );

        $msg = $capital->type === 'injection' 
            ? 'Permintaan modal tambahan telah ditolak.' 
            : 'Setoran pengembalian modal telah ditolak.';

        return response()->json([
            'message' => $msg,
            'capital' => $capital->load(['branch:id,name', 'approvedBy:id,name', 'bankAccount:id,bank_name,account_number,account_name']),
        ]);
    }

    /**
     * Void / Cancel an already approved transaction
     */
    public function void(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $capital = BranchCapital::findOrFail($id);
        $user = $request->user();

        // Revert bank or petty cash balance if it was approved
        if ($capital->status === 'approved') {
            $isBank = in_array(strtolower($capital->payment_method ?? ''), ['transfer', 'transfer bank', 'bank_transfer', 'qris']);
            $isCash = in_array(strtolower($capital->payment_method ?? ''), ['cash', 'tunai', 'kas']);

            if ($capital->type === 'injection' && ($isBank || $capital->bank_account_id)) {
                $bank = $capital->bank_account_id ? \App\Models\BankAccount::find($capital->bank_account_id) : null;
                if ($bank) $bank->increment('current_balance', $capital->amount);
            } elseif ($capital->type === 'return') {
                if ($isBank || $capital->bank_account_id) {
                    $bank = $capital->bank_account_id ? \App\Models\BankAccount::find($capital->bank_account_id) : null;
                    if ($bank) $bank->decrement('current_balance', $capital->amount);
                } elseif ($isCash) {
                    \App\Models\PettyCash::where('description', 'like', "%{$capital->reference_no}%")->delete();
                }
            }
        }

        $capital->update([
            'status' => 'rejected',
            'notes' => $capital->notes . "\n[Dibatalkan (Void) oleh {$user->name} pada " . Carbon::now()->format('d/m/Y H:i') . ": " . $request->reason . "]",
        ]);

        return response()->json([
            'message' => 'Transaksi modal berhasil dibatalkan (void).',
            'capital' => $capital->load(['branch:id,name', 'approvedBy:id,name', 'bankAccount:id,bank_name,account_number,account_name']),
        ]);
    }

    /**
     * Update an existing capital transaction
     */
    public function update(Request $request, $id)
    {
        $capital = BranchCapital::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'type' => 'required|in:injection,return',
            'category' => 'required|string|max:100',
            'amount' => 'required|numeric|min:1',
            'date' => 'required|date',
            'payment_method' => 'required|string|max:50',
            'bank_name' => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:50',
            'account_name' => 'nullable|string|max:100',
            'proof_file' => 'nullable|file|mimes:jpeg,png,jpg,pdf,webp|max:5120',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $proofPath = $capital->proof_file;
        if ($request->hasFile('proof_file')) {
            if ($capital->proof_file) {
                Storage::disk('public')->delete($capital->proof_file);
            }
            $proofPath = $request->file('proof_file')->store('branch_capitals', 'public');
        }

        $capital->update([
            'branch_id' => $request->branch_id,
            'type' => $request->type,
            'category' => $request->category,
            'amount' => $request->amount,
            'date' => $request->date,
            'payment_method' => $request->payment_method,
            'bank_name' => $request->bank_name,
            'account_number' => $request->account_number,
            'account_name' => $request->account_name,
            'proof_file' => $proofPath,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'message' => 'Transaksi modal berhasil diperbarui.',
            'capital' => $capital->load(['branch:id,name', 'user:id,name', 'approvedBy:id,name']),
        ]);
    }

    /**
     * Delete a capital transaction
     */
    public function destroy(Request $request, $id)
    {
        $capital = BranchCapital::findOrFail($id);

        if ($capital->proof_file) {
            Storage::disk('public')->delete($capital->proof_file);
        }

        $capital->delete();

        return response()->json(['message' => 'Transaksi modal berhasil dihapus.']);
    }

    /**
     * Kirim manual notifikasi setoran modal ke email Owner / Penerima.
     */
    public function sendEmail(Request $request, $id)
    {
        $request->validate([
            'email' => 'nullable|email',
        ]);

        $capital = BranchCapital::with(['branch', 'user'])->findOrFail($id);

        try {
            $log = \App\Services\EmailNotificationService::sendCapitalInstallmentAlert(
                $capital,
                $request->email,
                'manual',
                auth()->id()
            );

            if ($log->status === 'sent') {
                return response()->json([
                    'message' => 'Laporan setoran modal berhasil dikirim ke ' . $log->recipient_email,
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
     * Kirim manual rekapitulasi portofolio modal & ROI ke email Owner.
     */
    public function sendSummaryEmail(Request $request)
    {
        $request->validate([
            'email' => 'nullable|email',
        ]);

        // Fetch current summary data
        $summaryResponse = $this->summary($request);
        $summaryData = $summaryResponse->getData(true);

        try {
            $log = \App\Services\EmailNotificationService::sendCapitalSummaryReport(
                $summaryData,
                $request->email,
                'manual',
                auth()->id()
            );

            if ($log->status === 'sent') {
                return response()->json([
                    'message' => 'Laporan rekapitulasi modal & ROI berhasil dikirim ke ' . $log->recipient_email,
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
     * Ambil histori log email untuk transaksi modal ini.
     */
    public function emailLogs($id)
    {
        $logs = \App\Models\EmailLog::with(['user:id,name'])
            ->where('reference_type', BranchCapital::class)
            ->where('reference_id', (string) $id)
            ->latest()
            ->get();

        return response()->json(['data' => $logs]);
    }
}
