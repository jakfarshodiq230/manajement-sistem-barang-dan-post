<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountSetting;
use App\Models\JournalEntry;
use App\Models\JournalEntryItem;
use App\Models\Sale;
use App\Models\GoodsReceipt;
use App\Models\PayablePayment;
use App\Models\ReceivablePayment;
use App\Models\PettyCash;
use App\Models\BranchCapital;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class JournalService
{
    /**
     * Generate unique journal entry number
     */
    public static function generateEntryNumber($prefix = 'JV', $date = null)
    {
        $dateStr = $date ? date('Ymd', strtotime($date)) : date('Ymd');
        $count = JournalEntry::whereDate('created_at', today())->count() + 1;
        return sprintf('%s-%s-%04d', $prefix, $dateStr, $count);
    }

    /**
     * Create a balanced journal entry
     *
     * @param array $header [entry_date, branch_id, reference_type, reference_id, notes, created_by]
     * @param array $items [ ['account_id' => 1, 'debit' => 100000, 'credit' => 0, 'memo' => '...'], ... ]
     * @return JournalEntry
     * @throws Exception
     */
    public static function createEntry(array $header, array $items)
    {
        // 1. Calculate and validate total debit == total credit
        $totalDebit = 0;
        $totalCredit = 0;
        $validItems = [];

        foreach ($items as $item) {
            $debit = round(floatval($item['debit'] ?? 0), 2);
            $credit = round(floatval($item['credit'] ?? 0), 2);

            if ($debit > 0 || $credit > 0) {
                $totalDebit += $debit;
                $totalCredit += $credit;
                $validItems[] = [
                    'account_id' => $item['account_id'],
                    'debit' => $debit,
                    'credit' => $credit,
                    'memo' => $item['memo'] ?? ($header['notes'] ?? null),
                    'branch_id' => $item['branch_id'] ?? ($header['branch_id'] ?? null),
                ];
            }
        }

        if (empty($validItems)) {
            throw new Exception('Jurnal harus memiliki setidaknya satu baris transaksi bernilai.');
        }

        if (abs($totalDebit - $totalCredit) >= 0.01) {
            throw new Exception(sprintf(
                'Jurnal tidak seimbang (Unbalanced)! Total Debit (Rp %s) harus sama dengan Total Kredit (Rp %s). Selisih: Rp %s',
                number_format($totalDebit, 0, ',', '.'),
                number_format($totalCredit, 0, ',', '.'),
                number_format(abs($totalDebit - $totalCredit), 0, ',', '.')
            ));
        }

        return DB::transaction(function () use ($header, $validItems) {
            $entry = JournalEntry::create([
                'entry_number' => $header['entry_number'] ?? self::generateEntryNumber('JV', $header['entry_date'] ?? null),
                'entry_date' => $header['entry_date'] ?? now()->toDateString(),
                'branch_id' => $header['branch_id'] ?? null,
                'reference_type' => $header['reference_type'] ?? 'Manual',
                'reference_id' => $header['reference_id'] ?? null,
                'notes' => $header['notes'] ?? null,
                'status' => $header['status'] ?? 'posted',
                'created_by' => $header['created_by'] ?? auth()->id(),
            ]);

            foreach ($validItems as $row) {
                JournalEntryItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $row['account_id'],
                    'branch_id' => $row['branch_id'],
                    'debit' => $row['debit'],
                    'credit' => $row['credit'],
                    'memo' => $row['memo'],
                ]);
            }

            return $entry;
        });
    }

    /**
     * Helper to find account by setting key
     */
    public static function getAccountByKey($key, $branchId = null)
    {
        $id = \App\Models\AccountSetting::getAccountId($key, $branchId);
        if ($id) {
            return Account::find($id);
        }
        
        // Fallback to hardcoded codes if settings are not seeded
        $fallbackCodes = [
            'default_cash' => '1101',
            'default_bank' => '1102',
            'default_ar' => '1103',
            'default_inventory' => '1104',
            'default_ap' => '2101',
            'default_capital' => '3101',
            'default_sales' => '4101',
            'default_cogs' => '5101',
            'default_expense' => '6101',
        ];
        
        if (isset($fallbackCodes[$key])) {
            return Account::where('code', $fallbackCodes[$key])->first();
        }
        
        return null;
    }

    /**
     * Auto-journal for POS Sale
     */
    public static function journalForSale(Sale $sale)
    {
        try {
            // Avoid duplicate journal for same sale
            $existing = JournalEntry::where('reference_type', 'Sale')->where('reference_id', $sale->id)->first();
            if ($existing) {
                return $existing;
            }

            $totalAmount = floatval($sale->final_amount ?? $sale->total_amount ?? 0);
            if ($totalAmount <= 0) return null;

            $items = [];
            $branchId = $sale->branch_id;
            $method = strtolower($sale->payment_method ?? 'cash');

            // 1. Rekam Pembayaran
            if ($method === 'cash') {
                $cashAcc = self::getAccountByKey('default_cash', $branchId); // Kas Kasir
            } elseif ($method === 'bank_transfer' || $method === 'qris') {
                $cashAcc = self::getAccountByKey('default_bank', $branchId) ?? self::getAccountByKey('default_cash', $branchId); // Bank
            } elseif ($method === 'credit') {
                $cashAcc = self::getAccountByKey('default_ar', $branchId); // Piutang Usaha
            } else {
                $cashAcc = self::getAccountByKey('default_cash', $branchId); // Kas Kasir
            }

            $salesRevenueAcc = self::getAccountByKey('default_sales', $branchId); // Pendapatan Penjualan

            if ($cashAcc && $salesRevenueAcc) {
                $items[] = [
                    'account_id' => $cashAcc->id,
                    'debit' => $totalAmount,
                    'credit' => 0,
                    'memo' => 'Penerimaan Penjualan POS #' . ($sale->invoice_number ?? $sale->id),
                    'branch_id' => $branchId,
                ];

                $items[] = [
                    'account_id' => $salesRevenueAcc->id,
                    'debit' => 0,
                    'credit' => $totalAmount,
                    'memo' => 'Pendapatan Penjualan POS #' . ($sale->invoice_number ?? $sale->id),
                    'branch_id' => $branchId,
                ];
            }

            // 2. Cost of Goods Sold (HPP) & Inventory Reduction
            $cogsAmount = 0;
            if ($sale->items && $sale->items->count() > 0) {
                foreach ($sale->items as $sItem) {
                    $qty = floatval($sItem->qty ?? $sItem->quantity ?? 1);
                    $costPrice = floatval($sItem->cost_price ?? 0);
                    $cogsAmount += ($qty * $costPrice);
                }
            }

            if ($cogsAmount > 0) {
                $cogsAcc = self::getAccountByKey('default_cogs', $branchId); // HPP
                $invAcc = self::getAccountByKey('default_inventory', $branchId);  // Persediaan Barang

                if ($cogsAcc && $invAcc) {
                    $items[] = [
                        'account_id' => $cogsAcc->id,
                        'debit' => $cogsAmount,
                        'credit' => 0,
                        'memo' => 'HPP Penjualan #' . ($sale->invoice_number ?? $sale->id),
                        'branch_id' => $branchId,
                    ];
                    $items[] = [
                        'account_id' => $invAcc->id,
                        'debit' => 0,
                        'credit' => $cogsAmount,
                        'memo' => 'Pengurangan Persediaan Penjualan #' . ($sale->invoice_number ?? $sale->id),
                        'branch_id' => $branchId,
                    ];
                }
            }

            if (!empty($items)) {
                return self::createEntry([
                    'entry_number' => self::generateEntryNumber('POS', $sale->created_at),
                    'entry_date' => $sale->created_at ? $sale->created_at->toDateString() : now()->toDateString(),
                    'branch_id' => $branchId,
                    'reference_type' => 'Sale',
                    'reference_id' => $sale->id,
                    'notes' => 'Otomatis Penjualan POS Nota: ' . ($sale->invoice_number ?? $sale->id),
                    'status' => 'posted',
                    'created_by' => $sale->user_id ?? auth()->id(),
                ], $items);
            }
        } catch (Exception $e) {
            Log::error('Error auto-journaling POS sale: ' . $e->getMessage());
        }
        return null;
    }

    /**
     * Auto-journal for Goods Receipt (Penerimaan Barang / Hutang Pengadaan)
     */
    public static function journalForGoodsReceipt(GoodsReceipt $gr)
    {
        try {
            $existing = JournalEntry::where('reference_type', 'GoodsReceipt')->where('reference_id', $gr->id)->first();
            if ($existing) return $existing;

            $totalAmount = floatval($gr->total_amount ?? $gr->grand_total ?? 0);
            if ($totalAmount <= 0) return null;

            $branchId = $gr->branch_id;

            $invAcc = self::getAccountByKey('default_inventory', $branchId); // Persediaan Barang
            $apAcc = self::getAccountByKey('default_ap', $branchId);  // Hutang Usaha

            if ($invAcc && $apAcc) {
                $items = [
                    [
                        'account_id' => $invAcc->id,
                        'debit' => $totalAmount,
                        'credit' => 0,
                        'memo' => 'Penerimaan Stok Masuk GR #' . ($gr->gr_number ?? $gr->id),
                        'branch_id' => $branchId,
                    ],
                    [
                        'account_id' => $apAcc->id,
                        'debit' => 0,
                        'credit' => $totalAmount,
                        'memo' => 'Hutang Dagang Supplier GR #' . ($gr->gr_number ?? $gr->id),
                        'branch_id' => $branchId,
                    ],
                ];

                return self::createEntry([
                    'entry_number' => self::generateEntryNumber('GR', $gr->created_at),
                    'entry_date' => $gr->created_at ? $gr->created_at->toDateString() : now()->toDateString(),
                    'branch_id' => $branchId,
                    'reference_type' => 'GoodsReceipt',
                    'reference_id' => $gr->id,
                    'notes' => 'Otomatis Penerimaan Barang / Hutang GR: ' . ($gr->gr_number ?? $gr->id),
                    'status' => 'posted',
                    'created_by' => $gr->user_id ?? auth()->id(),
                ], $items);
            }
        } catch (Exception $e) {
            Log::error('Error auto-journaling GoodsReceipt: ' . $e->getMessage());
        }
        return null;
    }

    /**
     * Auto-journal for Payable Payment (Pelunasan Hutang Supplier)
     */
    public static function journalForPayablePayment(PayablePayment $payment)
    {
        try {
            $existing = JournalEntry::where('reference_type', 'PayablePayment')->where('reference_id', $payment->id)->first();
            if ($existing) return $existing;

            $amount = floatval($payment->amount ?? 0);
            if ($amount <= 0) return null;

            $branchId = $payment->payable->branch_id ?? 1;

            $apAcc = self::getAccountByKey('default_ap', $branchId); // Hutang Usaha
            $method = strtolower($payment->payment_method);
            $cashAcc = ($method === 'cash') ? self::getAccountByKey('default_cash', $branchId) : self::getAccountByKey('default_bank', $branchId);
            $branchId = $payment->payable ? $payment->payable->branch_id : ($payment->payableStatement ? $payment->payableStatement->branch_id : ($payment->statement ? $payment->statement->branch_id : 1));

            if ($apAcc && $cashAcc) {
                $items = [
                    [
                        'account_id' => $apAcc->id,
                        'debit' => $amount,
                        'credit' => 0,
                        'memo' => 'Pelunasan Hutang Supplier Pembayaran #' . ($payment->payment_number ?? $payment->id),
                        'branch_id' => $branchId,
                    ],
                    [
                        'account_id' => $cashAcc->id,
                        'debit' => 0,
                        'credit' => $amount,
                        'memo' => 'Pengeluaran Kas/Bank Pelunasan Hutang #' . ($payment->payment_number ?? $payment->id),
                        'branch_id' => $branchId,
                    ],
                ];

                return self::createEntry([
                    'entry_number' => self::generateEntryNumber('PAY', $payment->created_at),
                    'entry_date' => $payment->payment_date ? date('Y-m-d', strtotime($payment->payment_date)) : now()->toDateString(),
                    'branch_id' => $branchId,
                    'reference_type' => 'PayablePayment',
                    'reference_id' => $payment->id,
                    'notes' => 'Otomatis Pelunasan Hutang: ' . ($payment->payment_number ?? $payment->id),
                    'status' => 'posted',
                    'created_by' => $payment->created_by ?? auth()->id(),
                ], $items);
            }
        } catch (Exception $e) {
            Log::error('Error auto-journaling PayablePayment: ' . $e->getMessage());
        }
        return null;
    }

    /**
     * Auto-journal for Receivable Payment (Setoran Piutang Pelanggan)
     */
    public static function journalForReceivablePayment(ReceivablePayment $payment)
    {
        try {
            $existing = JournalEntry::where('reference_type', 'ReceivablePayment')->where('reference_id', $payment->id)->first();
            if ($existing) return $existing;

            $amount = floatval($payment->amount ?? 0);
            if ($amount <= 0) return null;

            $branchId = $payment->receivable->branch_id ?? $payment->branch_id ?? 1;

            $arAcc = self::getAccountByKey('default_ar', $branchId); // Piutang Usaha
            $method = strtolower($payment->payment_method);
            $cashAcc = ($method === 'bank_transfer' || $method === 'qris') ? self::getAccountByKey('default_bank', $branchId) : self::getAccountByKey('default_cash', $branchId);
            $branchId = $payment->receivable ? $payment->receivable->branch_id : 1;

            if ($arAcc && $cashAcc) {
                $items = [
                    [
                        'account_id' => $cashAcc->id,
                        'debit' => $amount,
                        'credit' => 0,
                        'memo' => 'Penerimaan Kas/Bank Setoran Piutang #' . $payment->id,
                        'branch_id' => $branchId,
                    ],
                    [
                        'account_id' => $arAcc->id,
                        'debit' => 0,
                        'credit' => $amount,
                        'memo' => 'Pengurangan Piutang Pelanggan Setoran #' . $payment->id,
                        'branch_id' => $branchId,
                    ],
                ];

                return self::createEntry([
                    'entry_number' => self::generateEntryNumber('REC', $payment->created_at),
                    'entry_date' => $payment->payment_date ? date('Y-m-d', strtotime($payment->payment_date)) : now()->toDateString(),
                    'branch_id' => $branchId,
                    'reference_type' => 'ReceivablePayment',
                    'reference_id' => $payment->id,
                    'notes' => 'Otomatis Setoran Piutang Pelanggan #' . $payment->id,
                    'status' => 'posted',
                    'created_by' => $payment->created_by ?? auth()->id(),
                ], $items);
            }
        } catch (Exception $e) {
            Log::error('Error auto-journaling ReceivablePayment: ' . $e->getMessage());
        }
        return null;
    }

    /**
     * Auto-journal for Petty Cash Expense
     */
    public static function journalForPettyCash(PettyCash $petty)
    {
        try {
            $existing = JournalEntry::where('reference_type', 'PettyCash')->where('reference_id', $petty->id)->first();
            if ($existing) return $existing;

            $amount = floatval($petty->amount ?? 0);
            if ($amount <= 0) return null;

            $branchId = $petty->branch_id;

            $expAcc = self::getAccountByKey('default_expense', $branchId); // Beban Operasional Kas Kecil
            $method = strtolower($petty->payment_method ?? 'cash');
            $cashAcc = ($method === 'bank_transfer') ? self::getAccountByKey('default_bank', $branchId) : self::getAccountByKey('default_cash', $branchId);
            $branchId = $petty->branch_id;

            if ($expAcc && $cashAcc) {
                $items = [
                    [
                        'account_id' => $expAcc->id,
                        'debit' => $amount,
                        'credit' => 0,
                        'memo' => 'Beban Operasional Kas Kecil: ' . ($petty->description ?? $petty->category),
                        'branch_id' => $branchId,
                    ],
                    [
                        'account_id' => $cashAcc->id,
                        'debit' => 0,
                        'credit' => $amount,
                        'memo' => 'Pengeluaran Kas Kecil Toko',
                        'branch_id' => $branchId,
                    ],
                ];

                return self::createEntry([
                    'entry_number' => self::generateEntryNumber('PC', $petty->expense_date ?? $petty->created_at),
                    'entry_date' => $petty->expense_date ? date('Y-m-d', strtotime($petty->expense_date)) : now()->toDateString(),
                    'branch_id' => $branchId,
                    'reference_type' => 'PettyCash',
                    'reference_id' => $petty->id,
                    'notes' => 'Otomatis Kas Kecil: ' . ($petty->description ?? 'Pengeluaran Toko'),
                    'status' => 'posted',
                    'created_by' => $petty->user_id ?? auth()->id(),
                ], $items);
            }
        } catch (Exception $e) {
            Log::error('Error auto-journaling PettyCash: ' . $e->getMessage());
        }
        return null;
    }

}
