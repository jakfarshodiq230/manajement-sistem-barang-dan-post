<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PettyCash;
use App\Models\CashShift;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PettyCashController extends Controller
{
    /**
     * List petty cash entries with filtering and summary
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $isGlobalUser = $user && ($user->can('manage all') || $user->can('Kasir (POS) Approve') || $user->can('Audit & Laporan Read') || !$user->branch_id);
        $branchId = $request->query('branch_id');
        
        // If not requested explicitly and not global admin, lock to user's assigned branch
        if (!$branchId && !$isGlobalUser && $user && $user->branch_id) {
            $branchId = $user->branch_id;
        }

        $category = $request->query('category');
        $paymentMethod = $request->query('payment_method');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $q = $request->query('q');

        $query = PettyCash::with(['user:id,name', 'branch:id,name', 'bankAccount'])->latest('date')->latest('id');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($category) {
            $query->where('category', $category);
        }

        if ($paymentMethod) {
            $query->where('payment_method', $paymentMethod);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        if ($q) {
            $query->where(function ($sub) use ($q) {
                $sub->where('description', 'like', "%{$q}%")
                    ->orWhere('category', 'like', "%{$q}%")
                    ->orWhereHas('bankAccount', function ($bSub) use ($q) {
                        $bSub->where('bank_name', 'like', "%{$q}%")
                             ->orWhere('account_number', 'like', "%{$q}%");
                    });
            });
        }

        $totalAmount = (clone $query)->sum('amount');
        $totalCash = (clone $query)->where('payment_method', 'cash')->sum('amount');
        $totalBank = (clone $query)->where('payment_method', 'bank_transfer')->sum('amount');

        $items = $query->paginate($request->query('itemsPerPage', 15));

        $defaultCategories = [
            'Operasional Toko',
            'Listrik & PLN',
            'Air & Galon Minum',
            'Bensin & Transportasi Kurir',
            'ATK & Kertas Thermal',
            'Konsumsi & Snack Lembur',
            'Kebersihan & Perlengkapan',
            'Lain-lain',
        ];
        $dbCategories = PettyCash::distinct()->pluck('category')->filter()->values()->all();
        $allCategories = array_values(array_unique(array_merge($defaultCategories, $dbCategories)));

        return response()->json([
            'data' => $items->items(),
            'total' => $items->total(),
            'totalAmount' => (float) $totalAmount,
            'totalCash' => (float) $totalCash,
            'totalBank' => (float) $totalBank,
            'currentPage' => $items->currentPage(),
            'lastPage' => $items->lastPage(),
            'categories' => $allCategories,
        ]);
    }

    /**
     * Store a new petty cash record
     */
    public function store(Request $request)
    {
        $request->validate([
            'category' => 'required|string|max:100',
            'payment_method' => 'nullable|in:cash,bank_transfer',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:1000',
            'date' => 'required|date',
            'receipt_image' => 'nullable|string',
            'branch_id' => 'nullable|integer',
        ]);

        $user = $request->user();
        $targetBranchId = $request->branch_id ?: ($user->branch_id ?: 1);
        $paymentMethod = $request->payment_method ?: 'cash';
        $amount = (float) $request->amount;

        DB::beginTransaction();
        try {
            $bankAccount = null;
            if ($paymentMethod === 'bank_transfer') {
                if (!$request->bank_account_id) {
                    return response()->json(['message' => 'Silakan pilih rekening bank sumber dana pengeluaran.'], 422);
                }
                $bankAccount = BankAccount::findOrFail($request->bank_account_id);
                if ((float) $bankAccount->current_balance < $amount) {
                    return response()->json([
                        'message' => "Saldo rekening {$bankAccount->bank_name} ({$bankAccount->account_number}) tidak mencukupi. Tersedia: Rp " . number_format($bankAccount->current_balance, 0, ',', '.') . ", dibutuhkan: Rp " . number_format($amount, 0, ',', '.') . "."
                    ], 422);
                }
                // Potong saldo rekening bank
                $bankAccount->decrement('current_balance', $amount);
            }

            // Check if there is an active open cash shift to link with (for cash payments)
            $activeShift = null;
            if ($paymentMethod === 'cash') {
                $activeShift = CashShift::where('user_id', $user->id)
                    ->where('status', 'open')
                    ->first();
            }

            $pettyCash = PettyCash::create([
                'branch_id' => $targetBranchId,
                'user_id' => $user->id,
                'cash_shift_id' => $activeShift ? $activeShift->id : null,
                'category' => $request->category,
                'payment_method' => $paymentMethod,
                'bank_account_id' => $bankAccount ? $bankAccount->id : null,
                'amount' => $amount,
                'description' => $request->description,
                'receipt_image' => $request->receipt_image,
                'date' => $request->date,
            ]);

            DB::commit();

            // Auto-journal in accounting
            try {
                \App\Services\JournalService::journalForPettyCash($pettyCash);
            } catch (\Exception $jEx) {
                \Illuminate\Support\Facades\Log::warning('Auto-journal PettyCash failed: ' . $jEx->getMessage());
            }

            return response()->json([
                'message' => 'Pengeluaran kas kecil berhasil dicatat' . ($bankAccount ? ' dan saldo bank berhasil dipotong.' : '.'),
                'data' => $pettyCash->load(['user:id,name', 'branch:id,name', 'bankAccount']),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal mencatat pengeluaran: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update petty cash record
     */
    public function update(Request $request, $id)
    {
        $pettyCash = PettyCash::findOrFail($id);

        $request->validate([
            'category' => 'required|string|max:100',
            'payment_method' => 'nullable|in:cash,bank_transfer',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:1000',
            'date' => 'required|date',
            'receipt_image' => 'nullable|string',
            'branch_id' => 'nullable|integer',
        ]);

        $newPaymentMethod = $request->payment_method ?: 'cash';
        $newAmount = (float) $request->amount;
        $newBankAccountId = $newPaymentMethod === 'bank_transfer' ? $request->bank_account_id : null;

        if ($newPaymentMethod === 'bank_transfer' && !$newBankAccountId) {
            return response()->json(['message' => 'Silakan pilih rekening bank sumber dana pengeluaran.'], 422);
        }

        DB::beginTransaction();
        try {
            // 1. Revert previous bank balance if old was bank_transfer
            if ($pettyCash->payment_method === 'bank_transfer' && $pettyCash->bank_account_id) {
                $oldBank = BankAccount::find($pettyCash->bank_account_id);
                if ($oldBank) {
                    $oldBank->increment('current_balance', $pettyCash->amount);
                }
            }

            // 2. Deduct new bank balance if new is bank_transfer
            if ($newPaymentMethod === 'bank_transfer' && $newBankAccountId) {
                $newBank = BankAccount::findOrFail($newBankAccountId);
                if ((float) $newBank->current_balance < $newAmount) {
                    DB::rollBack();
                    return response()->json([
                        'message' => "Saldo rekening {$newBank->bank_name} ({$newBank->account_number}) tidak mencukupi. Tersedia: Rp " . number_format($newBank->current_balance, 0, ',', '.') . ", dibutuhkan: Rp " . number_format($newAmount, 0, ',', '.') . "."
                    ], 422);
                }
                $newBank->decrement('current_balance', $newAmount);
            }

            $pettyCash->update([
                'category' => $request->category,
                'payment_method' => $newPaymentMethod,
                'bank_account_id' => $newBankAccountId,
                'amount' => $newAmount,
                'description' => $request->description,
                'date' => $request->date,
                'receipt_image' => $request->receipt_image,
                'branch_id' => $request->branch_id ?: $pettyCash->branch_id,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Pengeluaran kas kecil berhasil diperbarui.',
                'data' => $pettyCash->load(['user:id,name', 'branch:id,name', 'bankAccount']),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal memperbarui pengeluaran: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete petty cash record
     */
    public function destroy($id)
    {
        $pettyCash = PettyCash::findOrFail($id);

        DB::beginTransaction();
        try {
            // Revert bank account balance if was paid via bank
            if ($pettyCash->payment_method === 'bank_transfer' && $pettyCash->bank_account_id) {
                $bank = BankAccount::find($pettyCash->bank_account_id);
                if ($bank) {
                    $bank->increment('current_balance', $pettyCash->amount);
                }
            }

            $pettyCash->delete();

            DB::commit();

            return response()->json([
                'message' => 'Catatan kas kecil berhasil dihapus' . ($pettyCash->payment_method === 'bank_transfer' ? ' dan saldo bank berhasil dikembalikan.' : '.'),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menghapus pengeluaran: ' . $e->getMessage()], 500);
        }
    }
}

