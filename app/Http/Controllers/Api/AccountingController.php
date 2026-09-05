<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountSetting;
use App\Models\JournalEntry;
use App\Models\JournalEntryItem;
use App\Models\Sale;
use App\Models\GoodsReceipt;
use App\Models\PayablePayment;
use App\Models\ReceivablePayment;
use App\Models\PettyCash;
use App\Models\Branch;
use App\Services\JournalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Exception;

class AccountingController extends Controller
{
    /**
     * Get Accounting Dashboard Overview Metrics
     */
    public function overview(Request $request)
    {
        $branchId = $request->query('branch_id');
        $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', now()->endOfMonth()->toDateString());

        // 1. Total Assets
        $assetAccounts = Account::where('type', 'asset')->where('is_active', true)->get();
        $totalAssets = 0;
        foreach ($assetAccounts as $acc) {
            $totalAssets += $this->calculateAccountBalance($acc, $branchId, $endDate);
        }

        // 2. Total Liabilities
        $liabilityAccounts = Account::where('type', 'liability')->where('is_active', true)->get();
        $totalLiabilities = 0;
        foreach ($liabilityAccounts as $acc) {
            $totalLiabilities += $this->calculateAccountBalance($acc, $branchId, $endDate);
        }

        // 3. Total Equity
        $equityAccounts = Account::where('type', 'equity')->where('is_active', true)->get();
        $totalEquity = 0;
        foreach ($equityAccounts as $acc) {
            $bal = $this->calculateAccountBalance($acc, $branchId, $endDate);
            if ($acc->normal_balance === 'debit') {
                $totalEquity -= $bal;
            } else {
                $totalEquity += $bal;
            }
        }

        // 4. Period Revenue & Expense
        $revenueAccounts = Account::where('type', 'revenue')->where('is_active', true)->get();
        $totalRevenue = 0;
        foreach ($revenueAccounts as $acc) {
            $totalRevenue += $this->calculateAccountMovement($acc, $branchId, $startDate, $endDate);
        }

        $cogsAccounts = Account::where('type', 'cogs')->where('is_active', true)->get();
        $totalCogs = 0;
        foreach ($cogsAccounts as $acc) {
            $totalCogs += $this->calculateAccountMovement($acc, $branchId, $startDate, $endDate);
        }

        $expenseAccounts = Account::where('type', 'expense')->where('is_active', true)->get();
        $totalExpenses = 0;
        foreach ($expenseAccounts as $acc) {
            $totalExpenses += $this->calculateAccountMovement($acc, $branchId, $startDate, $endDate);
        }

        $netProfit = $totalRevenue - $totalCogs - $totalExpenses;
        $totalJournalsCount = JournalEntry::when($branchId, fn($q) => $q->where('branch_id', $branchId))->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_assets' => round($totalAssets, 2),
                'total_liabilities' => round($totalLiabilities, 2),
                'total_equity' => round($totalEquity, 2),
                'total_revenue' => round($totalRevenue, 2),
                'total_cogs' => round($totalCogs, 2),
                'total_expenses' => round($totalExpenses, 2),
                'net_profit' => round($netProfit, 2),
                'total_journals_count' => $totalJournalsCount,
                'is_balance_sheet_balanced' => abs($totalAssets - ($totalLiabilities + $totalEquity + $netProfit)) < 1.0,
            ],
        ]);
    }

    /**
     * Get Chart of Accounts (COA)
     */
    public function getAccounts(Request $request)
    {
        $q = $request->query('q');
        $type = $request->query('type');
        $branchId = $request->query('branch_id');
        $isTree = $request->query('tree', false);

        $query = Account::with(['parent', 'children'])
            ->when($q, function ($query, $q) {
                $query->where(function ($w) use ($q) {
                    $w->where('code', 'like', "%{$q}%")
                      ->orWhere('name', 'like', "%{$q}%");
                });
            })
            ->when($type, fn($query, $type) => $query->where('type', $type))
            ->orderBy('code');

        if ($isTree) {
            $accounts = $query->whereNull('parent_id')->with('children.children')->get();
        } else {
            $accounts = $query->get();
        }

        // Attach calculated balance
        $accounts->map(function ($acc) use ($branchId) {
            $acc->current_balance = $this->calculateAccountBalance($acc, $branchId);
            return $acc;
        });

        return response()->json([
            'success' => true,
            'data' => $accounts,
        ]);
    }

    /**
     * Store new account
     */
    public function storeAccount(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:30|unique:accounts,code',
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,equity,revenue,cogs,expense',
            'category' => 'nullable|string|max:60',
            'normal_balance' => 'required|in:debit,credit',
            'parent_id' => 'nullable|exists:accounts,id',
            'branch_id' => 'nullable|exists:branches,id',
            'opening_balance' => 'nullable|numeric',
            'description' => 'nullable|string',
        ]);

        $account = Account::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Akun COA berhasil ditambahkan.',
            'data' => $account,
        ], 201);
    }

    /**
     * Update account
     */
    public function updateAccount(Request $request, $id)
    {
        $account = Account::findOrFail($id);

        $validated = $request->validate([
            'code' => 'required|string|max:30|unique:accounts,code,' . $account->id,
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,equity,revenue,cogs,expense',
            'category' => 'nullable|string|max:60',
            'normal_balance' => 'required|in:debit,credit',
            'parent_id' => 'nullable|exists:accounts,id',
            'branch_id' => 'nullable|exists:branches,id',
            'opening_balance' => 'nullable|numeric',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string',
        ]);

        $account->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Akun COA berhasil diperbarui.',
            'data' => $account,
        ]);
    }

    /**
     * Delete account
     */
    public function deleteAccount($id)
    {
        $account = Account::findOrFail($id);

        if ($account->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'Akun sistem tidak dapat dihapus.',
            ], 422);
        }

        if ($account->journalItems()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Akun tidak dapat dihapus karena sudah memiliki riwayat mutasi jurnal.',
            ], 422);
        }

        $account->delete();

        return response()->json([
            'success' => true,
            'message' => 'Akun COA berhasil dihapus.',
        ]);
    }

    /**
     * Get Journal Entries List
     */
    public function getJournalEntries(Request $request)
    {
        $q = $request->query('q');
        $branchId = $request->query('branch_id');
        $refType = $request->query('reference_type');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $itemsPerPage = $request->query('itemsPerPage', 15);

        $query = JournalEntry::with(['items.account', 'branch', 'user'])
            ->when($q, function ($query, $q) {
                $query->where(function ($w) use ($q) {
                    $w->where('entry_number', 'like', "%{$q}%")
                      ->orWhere('notes', 'like', "%{$q}%");
                });
            })
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($refType, fn($q) => $q->where('reference_type', $refType))
            ->when($startDate, fn($q) => $q->whereDate('entry_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('entry_date', '<=', $endDate))
            ->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc');

        $paginator = $query->paginate($itemsPerPage);

        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ]);
    }

    /**
     * Store Manual Journal Voucher (JV)
     */
    public function storeManualJournal(Request $request)
    {
        $request->validate([
            'entry_date' => 'required|date',
            'branch_id' => 'nullable|exists:branches,id',
            'notes' => 'required|string',
            'items' => 'required|array|min:2',
            'items.*.account_id' => 'required|exists:accounts,id',
            'items.*.debit' => 'required|numeric|min:0',
            'items.*.credit' => 'required|numeric|min:0',
            'items.*.memo' => 'nullable|string|max:255',
        ]);

        try {
            $entry = JournalService::createEntry([
                'entry_date' => $request->entry_date,
                'branch_id' => $request->branch_id,
                'reference_type' => 'Manual',
                'notes' => $request->notes,
                'status' => 'posted',
                'created_by' => auth()->id(),
            ], $request->items);

            return response()->json([
                'success' => true,
                'message' => 'Jurnal Penyesuaian Manual berhasil disimpan dan seimbang.',
                'data' => $entry->load('items.account'),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get General Ledger (Buku Besar)
     */
    public function getGeneralLedger(Request $request)
    {
        $accountId = $request->query('account_id');
        $branchId = $request->query('branch_id');
        $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', now()->endOfMonth()->toDateString());

        if (!$accountId) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan pilih Akun COA terlebih dahulu.',
            ], 422);
        }

        $account = Account::findOrFail($accountId);

        // 1. Calculate Beginning Balance before startDate
        $prevDebit = JournalEntryItem::where('account_id', $account->id)
            ->whereHas('journalEntry', function ($q) use ($startDate, $branchId) {
                $q->whereDate('entry_date', '<', $startDate)
                  ->when($branchId, fn($b) => $b->where('branch_id', $branchId));
            })->sum('debit') ?: 0;

        $prevCredit = JournalEntryItem::where('account_id', $account->id)
            ->whereHas('journalEntry', function ($q) use ($startDate, $branchId) {
                $q->whereDate('entry_date', '<', $startDate)
                  ->when($branchId, fn($b) => $b->where('branch_id', $branchId));
            })->sum('credit') ?: 0;

        $openingBalance = floatval($account->opening_balance ?? 0);
        if ($account->normal_balance === 'debit') {
            $beginningBalance = $openingBalance + ($prevDebit - $prevCredit);
        } else {
            $beginningBalance = $openingBalance + ($prevCredit - $prevDebit);
        }

        // 2. Fetch Period Transactions
        $items = JournalEntryItem::with(['journalEntry.branch', 'journalEntry.user'])
            ->where('account_id', $account->id)
            ->whereHas('journalEntry', function ($q) use ($startDate, $endDate, $branchId) {
                $q->whereBetween('entry_date', [$startDate, $endDate])
                  ->when($branchId, fn($b) => $b->where('branch_id', $branchId));
            })
            ->join('journal_entries', 'journal_entry_items.journal_entry_id', '=', 'journal_entries.id')
            ->orderBy('journal_entries.entry_date', 'asc')
            ->orderBy('journal_entries.id', 'asc')
            ->select('journal_entry_items.*')
            ->get();

        $runningBalance = $beginningBalance;
        $ledgerRows = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($items as $item) {
            $debit = floatval($item->debit);
            $credit = floatval($item->credit);
            $totalDebit += $debit;
            $totalCredit += $credit;

            if ($account->normal_balance === 'debit') {
                $runningBalance += ($debit - $credit);
            } else {
                $runningBalance += ($credit - $debit);
            }

            $entry = $item->journalEntry;
            $entryDate = ($entry && $entry->entry_date) ? $entry->entry_date->format('Y-m-d') : '-';
            $entryNumber = $entry ? $entry->entry_number : '-';
            $refType = $entry ? $entry->reference_type : null;
            $refId = $entry ? $entry->reference_id : null;
            $notes = $item->memo ?: ($entry ? $entry->notes : '-');
            $branchName = ($entry && $entry->branch) ? $entry->branch->name : 'Semua Cabang';

            $ledgerRows[] = [
                'id' => $item->id,
                'entry_date' => $entryDate,
                'entry_number' => $entryNumber,
                'reference_type' => $refType,
                'reference_id' => $refId,
                'notes' => $notes,
                'branch_name' => $branchName,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => round($runningBalance, 2),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'account' => $account,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'beginning_balance' => round($beginningBalance, 2),
                'total_debit' => round($totalDebit, 2),
                'total_credit' => round($totalCredit, 2),
                'ending_balance' => round($runningBalance, 2),
                'transactions' => $ledgerRows,
            ],
        ]);
    }

    /**
     * Get Trial Balance (Neraca Saldo)
     */
    public function getTrialBalance(Request $request)
    {
        $branchId = $request->query('branch_id');
        $asOfDate = $request->query('as_of_date', now()->toDateString());

        $accounts = Account::where('is_active', true)->orderBy('code')->get();
        $rows = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($accounts as $acc) {
            $balance = $this->calculateAccountBalance($acc, $branchId, $asOfDate);

            if (abs($balance) > 0.001) {
                $debit = 0;
                $credit = 0;

                if ($acc->normal_balance === 'debit') {
                    if ($balance >= 0) {
                        $debit = $balance;
                    } else {
                        $credit = abs($balance);
                    }
                } else {
                    if ($balance >= 0) {
                        $credit = $balance;
                    } else {
                        $debit = abs($balance);
                    }
                }

                $totalDebit += $debit;
                $totalCredit += $credit;

                $rows[] = [
                    'id' => $acc->id,
                    'code' => $acc->code,
                    'name' => $acc->name,
                    'type' => $acc->type,
                    'category' => $acc->category,
                    'normal_balance' => $acc->normal_balance,
                    'debit' => round($debit, 2),
                    'credit' => round($credit, 2),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'as_of_date' => $asOfDate,
                'rows' => $rows,
                'total_debit' => round($totalDebit, 2),
                'total_credit' => round($totalCredit, 2),
                'difference' => round(abs($totalDebit - $totalCredit), 2),
                'is_balanced' => abs($totalDebit - $totalCredit) < 1.0,
            ],
        ]);
    }

    /**
     * Get Balance Sheet (Neraca Keuangan)
     */
    public function getBalanceSheet(Request $request)
    {
        $branchId = $request->query('branch_id');
        $asOfDate = $request->query('as_of_date', now()->toDateString());

        // 1. Assets
        $assetAccounts = Account::where('type', 'asset')->where('is_active', true)->orderBy('code')->get();
        $assets = [];
        $totalAssets = 0;

        foreach ($assetAccounts as $acc) {
            $bal = $this->calculateAccountBalance($acc, $branchId, $asOfDate);
            if (abs($bal) > 0.01) {
                $assets[] = [
                    'id' => $acc->id,
                    'code' => $acc->code,
                    'name' => $acc->name,
                    'category' => $acc->category,
                    'balance' => round($bal, 2),
                ];
                $totalAssets += $bal;
            }
        }

        // 2. Liabilities
        $liabilityAccounts = Account::where('type', 'liability')->where('is_active', true)->orderBy('code')->get();
        $liabilities = [];
        $totalLiabilities = 0;

        foreach ($liabilityAccounts as $acc) {
            $bal = $this->calculateAccountBalance($acc, $branchId, $asOfDate);
            if (abs($bal) > 0.01) {
                $liabilities[] = [
                    'id' => $acc->id,
                    'code' => $acc->code,
                    'name' => $acc->name,
                    'category' => $acc->category,
                    'balance' => round($bal, 2),
                ];
                $totalLiabilities += $bal;
            }
        }

        // 3. Equity
        $equityAccounts = Account::where('type', 'equity')->where('is_active', true)->orderBy('code')->get();
        $equities = [];
        $totalEquity = 0;

        foreach ($equityAccounts as $acc) {
            $bal = $this->calculateAccountBalance($acc, $branchId, $asOfDate);
            if (abs($bal) > 0.01) {
                $equities[] = [
                    'id' => $acc->id,
                    'code' => $acc->code,
                    'name' => $acc->name,
                    'category' => $acc->category,
                    'normal_balance' => $acc->normal_balance,
                    'balance' => round($bal, 2),
                ];
                if ($acc->normal_balance === 'debit') {
                    $totalEquity -= $bal;
                } else {
                    $totalEquity += $bal;
                }
            }
        }

        // 4. Current Period Net Profit (Revenue - COGS - Expense) up to asOfDate
        $revTotal = 0;
        foreach (Account::where('type', 'revenue')->get() as $r) {
            $revTotal += $this->calculateAccountMovement($r, $branchId, '2000-01-01', $asOfDate);
        }
        $cogsTotal = 0;
        foreach (Account::where('type', 'cogs')->get() as $c) {
            $cogsTotal += $this->calculateAccountMovement($c, $branchId, '2000-01-01', $asOfDate);
        }
        $expTotal = 0;
        foreach (Account::where('type', 'expense')->get() as $e) {
            $expTotal += $this->calculateAccountMovement($e, $branchId, '2000-01-01', $asOfDate);
        }
        $currentEarnings = $revTotal - $cogsTotal - $expTotal;

        $totalEquityAndEarnings = $totalEquity + $currentEarnings;
        $totalLiabilitiesAndEquity = $totalLiabilities + $totalEquityAndEarnings;

        return response()->json([
            'success' => true,
            'data' => [
                'as_of_date' => $asOfDate,
                'assets' => $assets,
                'total_assets' => round($totalAssets, 2),
                'liabilities' => $liabilities,
                'total_liabilities' => round($totalLiabilities, 2),
                'equities' => $equities,
                'total_equity' => round($totalEquity, 2),
                'current_period_earnings' => round($currentEarnings, 2),
                'total_equity_with_earnings' => round($totalEquityAndEarnings, 2),
                'total_liabilities_and_equity' => round($totalLiabilitiesAndEquity, 2),
                'difference' => round(abs($totalAssets - $totalLiabilitiesAndEquity), 2),
                'is_balanced' => abs($totalAssets - $totalLiabilitiesAndEquity) < 1.0,
            ],
        ]);
    }

    /**
     * Get Income Statement (Laporan Laba Rugi Akuntansi)
     */
    public function getIncomeStatement(Request $request)
    {
        $branchId = $request->query('branch_id');
        $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', now()->endOfMonth()->toDateString());

        // Revenues
        $revAccounts = Account::where('type', 'revenue')->where('is_active', true)->orderBy('code')->get();
        $revenues = [];
        $totalRevenue = 0;
        foreach ($revAccounts as $acc) {
            $val = $this->calculateAccountMovement($acc, $branchId, $startDate, $endDate);
            if (abs($val) > 0.01) {
                $revenues[] = ['code' => $acc->code, 'name' => $acc->name, 'amount' => round($val, 2)];
                $totalRevenue += $val;
            }
        }

        // COGS
        $cogsAccounts = Account::where('type', 'cogs')->where('is_active', true)->orderBy('code')->get();
        $cogsList = [];
        $totalCogs = 0;
        foreach ($cogsAccounts as $acc) {
            $val = $this->calculateAccountMovement($acc, $branchId, $startDate, $endDate);
            if (abs($val) > 0.01) {
                $cogsList[] = ['code' => $acc->code, 'name' => $acc->name, 'amount' => round($val, 2)];
                $totalCogs += $val;
            }
        }

        $grossProfit = $totalRevenue - $totalCogs;

        // Operating Expenses
        $expAccounts = Account::where('type', 'expense')->where('is_active', true)->orderBy('code')->get();
        $expenses = [];
        $totalExpenses = 0;
        foreach ($expAccounts as $acc) {
            $val = $this->calculateAccountMovement($acc, $branchId, $startDate, $endDate);
            if (abs($val) > 0.01) {
                $expenses[] = ['code' => $acc->code, 'name' => $acc->name, 'amount' => round($val, 2)];
                $totalExpenses += $val;
            }
        }

        $netProfit = $grossProfit - $totalExpenses;

        return response()->json([
            'success' => true,
            'data' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'revenues' => $revenues,
                'total_revenue' => round($totalRevenue, 2),
                'cogs' => $cogsList,
                'total_cogs' => round($totalCogs, 2),
                'gross_profit' => round($grossProfit, 2),
                'expenses' => $expenses,
                'total_expenses' => round($totalExpenses, 2),
                'net_profit' => round($netProfit, 2),
            ],
        ]);
    }

    /**
     * Batch Sync / Backfill Historical Transactions to Journals
     */
    public function syncHistoricalTransactions(Request $request)
    {
        $limit = $request->query('limit', 200);
        $syncedSales = 0;
        $syncedGR = 0;
        $syncedPay = 0;
        $syncedRec = 0;
        $syncedPetty = 0;
        $syncedCap = 0;

        // 1. Sales
        $sales = Sale::with('saleItems.productBatch')->limit($limit)->get();
        foreach ($sales as $sale) {
            if (JournalService::journalForSale($sale)) $syncedSales++;
        }

        // 2. Goods Receipts
        $grs = GoodsReceipt::limit($limit)->get();
        foreach ($grs as $gr) {
            if (JournalService::journalForGoodsReceipt($gr)) $syncedGR++;
        }

        // 3. Payable Payments
        $payPayments = PayablePayment::with('payable')->limit($limit)->get();
        foreach ($payPayments as $pay) {
            if (JournalService::journalForPayablePayment($pay)) $syncedPay++;
        }

        // 4. Receivable Payments
        $recPayments = ReceivablePayment::with('receivable')->limit($limit)->get();
        foreach ($recPayments as $rec) {
            if (JournalService::journalForReceivablePayment($rec)) $syncedRec++;
        }

        // 5. Petty Cash
        $pettyList = PettyCash::limit($limit)->get();
        foreach ($pettyList as $pc) {
            if (JournalService::journalForPettyCash($pc)) $syncedPetty++;
        }

        // 6. Branch Capital
        $capitals = BranchCapital::limit($limit)->get();
        foreach ($capitals as $cap) {
            if (JournalService::journalForBranchCapital($cap)) $syncedCap++;
        }

        $totalSynced = $syncedSales + $syncedGR + $syncedPay + $syncedRec + $syncedPetty + $syncedCap;

        return response()->json([
            'success' => true,
            'message' => "Sinkronisasi selesai. Sebanyak {$totalSynced} transaksi berhasil dibukukan ke Jurnal Umum Akuntansi.",
            'details' => [
                'sales' => $syncedSales,
                'goods_receipts' => $syncedGR,
                'payable_payments' => $syncedPay,
                'receivable_payments' => $syncedRec,
                'petty_cash' => $syncedPetty,
                'branch_capitals' => $syncedCap,
            ],
        ]);
    }

    /**
     * Helper to calculate Account Balance as of a date
     */
    private function calculateAccountBalance(Account $acc, $branchId = null, $asOfDate = null)
    {
        $query = JournalEntryItem::where('account_id', $acc->id)
            ->whereHas('journalEntry', function ($q) use ($branchId, $asOfDate) {
                $q->when($branchId, fn($b) => $b->where('branch_id', $branchId))
                  ->when($asOfDate, fn($d) => $d->whereDate('entry_date', '<=', $asOfDate));
            });

        $debit = $query->sum('debit') ?: 0;
        $credit = $query->sum('credit') ?: 0;
        $opening = floatval($acc->opening_balance ?? 0);

        if ($acc->normal_balance === 'debit') {
            return $opening + ($debit - $credit);
        } else {
            return $opening + ($credit - $debit);
        }
    }

    /**
     * Helper to calculate period movement (for P&L accounts)
     */
    private function calculateAccountMovement(Account $acc, $branchId = null, $startDate = null, $endDate = null)
    {
        $query = JournalEntryItem::where('account_id', $acc->id)
            ->whereHas('journalEntry', function ($q) use ($branchId, $startDate, $endDate) {
                $q->when($branchId, fn($b) => $b->where('branch_id', $branchId))
                  ->when($startDate, fn($d) => $d->whereDate('entry_date', '>=', $startDate))
                  ->when($endDate, fn($d) => $d->whereDate('entry_date', '<=', $endDate));
            });

        $debit = $query->sum('debit') ?: 0;
        $credit = $query->sum('credit') ?: 0;

        if ($acc->normal_balance === 'debit') {
            return $debit - $credit;
        } else {
            return $credit - $debit;
        }
    }

    private function getRefTypeLabel($type)
    {
        switch ($type) {
            case 'Sale': return 'Penjualan POS';
            case 'GoodsReceipt': return 'Penerimaan Gudang';
            case 'PayablePayment': return 'Bayar Hutang';
            case 'ReceivablePayment': return 'Setoran Piutang';
            case 'PettyCash': return 'Kas Kecil';
            case 'BranchCapital': return 'Modal & ROI';
            case 'Manual': return 'Jurnal Penyesuaian';
            default: return $type ?: 'Umum';
        }
    }

    /**
     * Export Journal Entries PDF
     */
    public function exportJournalPdf(Request $request)
    {
        $q = $request->query('q');
        $branchId = $request->query('branch_id');
        $refType = $request->query('reference_type');
        $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', now()->endOfMonth()->toDateString());

        $query = JournalEntry::with(['items.account', 'branch', 'user'])
            ->when($q, function ($query, $q) {
                $query->where(function ($w) use ($q) {
                    $w->where('entry_number', 'like', "%{$q}%")
                      ->orWhere('notes', 'like', "%{$q}%");
                });
            })
            ->when($branchId && $branchId !== 'all', fn($q) => $q->where('branch_id', $branchId))
            ->when($refType && $refType !== 'all', fn($q) => $q->where('reference_type', $refType))
            ->when($startDate, fn($q) => $q->whereDate('entry_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('entry_date', '<=', $endDate))
            ->orderBy('entry_date', 'asc')
            ->orderBy('id', 'asc');

        $entries = $query->get();

        $journals = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($entries as $e) {
            $jItems = [];
            foreach ($e->items as $item) {
                $deb = floatval($item->debit);
                $crd = floatval($item->credit);
                $totalDebit += $deb;
                $totalCredit += $crd;

                $jItems[] = [
                    'account_code' => $item->account ? $item->account->code : '-',
                    'account_name' => $item->account ? $item->account->name : '-',
                    'debit' => $deb,
                    'credit' => $crd,
                    'memo' => $item->memo,
                ];
            }

            $journals[] = [
                'entry_date' => $e->entry_date ? $e->entry_date->format('d/m/Y') : '-',
                'entry_number' => $e->entry_number,
                'reference_type' => $e->reference_type,
                'ref_label' => $this->getRefTypeLabel($e->reference_type),
                'branch_name' => $e->branch ? $e->branch->name : 'Semua Cabang',
                'notes' => $e->notes,
                'items' => $jItems,
            ];
        }

        $branchName = 'Semua Cabang';
        if ($branchId && $branchId !== 'all') {
            $branchObj = Branch::find($branchId);
            if ($branchObj) $branchName = $branchObj->name;
        }

        $user = auth()->user();
        $userName = $user ? $user->name : 'Administrator';
        $userRole = ($user && $user->role) ? $user->role->name : 'Akuntan / Finance';

        $signerPayload = "TANDA TANGAN DIGITAL (AKUNTAN / PETUGAS JURNAL)\n"
            . "============================================\n"
            . "Nama          : " . $userName . "\n"
            . "Jabatan       : " . $userRole . "\n"
            . "Waktu TTD     : " . date('d/m/Y H:i:s') . "\n"
            . "Dokumen       : Jurnal Umum (" . $startDate . " s/d " . $endDate . ")\n"
            . "Status        : TERTANDA DIGITAL SAH (VERIFIED)";
        $signerQrCode = base64_encode(QrCode::format('svg')->size(70)->generate($signerPayload));

        $docPayload = "LEMBAR PENGESAHAN DOKUMEN JURNAL RESMI\n"
            . "======================================\n"
            . "Sistem        : PT Dumai Akuntansi ERP\n"
            . "Unit          : " . $branchName . "\n"
            . "Total Jurnal  : " . count($journals) . " Transaksi\n"
            . "Total Debit   : Rp " . number_format($totalDebit, 0, ',', '.') . "\n"
            . "Total Kredit  : Rp " . number_format($totalCredit, 0, ',', '.') . "\n"
            . "Status        : LENGKAP & TERVERIFIKASI";
        $documentQrCode = base64_encode(QrCode::format('svg')->size(70)->generate($docPayload));

        $pdfData = [
            'startDate' => date('d/m/Y', strtotime($startDate)),
            'endDate' => date('d/m/Y', strtotime($endDate)),
            'branchName' => $branchName,
            'journals' => $journals,
            'totalJournals' => count($journals),
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'userName' => $userName,
            'userRole' => $userRole,
            'ownerName' => 'Direksi & Owner PT Dumai',
            'signerQrCode' => $signerQrCode,
            'documentQrCode' => $documentQrCode,
        ];

        $pdf = Pdf::loadView('pdf.accounting_journal', $pdfData);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('Jurnal_Transaksi_' . date('Ymd', strtotime($startDate)) . '_' . date('Ymd', strtotime($endDate)) . '.pdf');
    }

    /**
     * Export General Ledger PDF
     */
    public function exportLedgerPdf(Request $request)
    {
        $accountId = $request->query('account_id');
        $branchId = $request->query('branch_id');
        $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', now()->endOfMonth()->toDateString());

        if (!$accountId) {
            abort(422, 'Silakan tentukan akun COA.');
        }

        $account = Account::findOrFail($accountId);

        // 1. Calculate Beginning Balance before startDate
        $prevDebit = JournalEntryItem::where('account_id', $account->id)
            ->whereHas('journalEntry', function ($q) use ($startDate, $branchId) {
                $q->whereDate('entry_date', '<', $startDate)
                  ->when($branchId && $branchId !== 'all', fn($b) => $b->where('branch_id', $branchId));
            })->sum('debit') ?: 0;

        $prevCredit = JournalEntryItem::where('account_id', $account->id)
            ->whereHas('journalEntry', function ($q) use ($startDate, $branchId) {
                $q->whereDate('entry_date', '<', $startDate)
                  ->when($branchId && $branchId !== 'all', fn($b) => $b->where('branch_id', $branchId));
            })->sum('credit') ?: 0;

        $openingBalance = floatval($account->opening_balance ?? 0);
        if ($account->normal_balance === 'debit') {
            $beginningBalance = $openingBalance + ($prevDebit - $prevCredit);
        } else {
            $beginningBalance = $openingBalance + ($prevCredit - $prevDebit);
        }

        // 2. Fetch Period Transactions
        $items = JournalEntryItem::with(['journalEntry.branch', 'journalEntry.user'])
            ->where('account_id', $account->id)
            ->whereHas('journalEntry', function ($q) use ($startDate, $endDate, $branchId) {
                $q->whereBetween('entry_date', [$startDate, $endDate])
                  ->when($branchId && $branchId !== 'all', fn($b) => $b->where('branch_id', $branchId));
            })
            ->join('journal_entries', 'journal_entry_items.journal_entry_id', '=', 'journal_entries.id')
            ->orderBy('journal_entries.entry_date', 'asc')
            ->orderBy('journal_entries.id', 'asc')
            ->select('journal_entry_items.*')
            ->get();

        $runningBalance = $beginningBalance;
        $transactions = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($items as $item) {
            $debit = floatval($item->debit);
            $credit = floatval($item->credit);
            $totalDebit += $debit;
            $totalCredit += $credit;

            if ($account->normal_balance === 'debit') {
                $runningBalance += ($debit - $credit);
            } else {
                $runningBalance += ($credit - $debit);
            }

            $entry = $item->journalEntry;
            $transactions[] = [
                'entry_date' => $entry && $entry->entry_date ? $entry->entry_date->format('d/m/Y') : '-',
                'entry_number' => $entry ? $entry->entry_number : '-',
                'reference_type' => $entry ? $this->getRefTypeLabel($entry->reference_type) : '',
                'notes' => $item->memo ?: ($entry ? $entry->notes : '-'),
                'branch_name' => ($entry && $entry->branch) ? $entry->branch->name : 'Semua Cabang',
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $runningBalance,
            ];
        }

        $branchName = 'Semua Cabang';
        if ($branchId && $branchId !== 'all') {
            $branchObj = Branch::find($branchId);
            if ($branchObj) $branchName = $branchObj->name;
        }

        $user = auth()->user();
        $userName = $user ? $user->name : 'Administrator';
        $userRole = ($user && $user->role) ? $user->role->name : 'Akuntan / Finance';

        $signerPayload = "TANDA TANGAN RESMI BUKU BESAR (GENERAL LEDGER)\n"
            . "============================================\n"
            . "Akun          : " . $account->code . " - " . $account->name . "\n"
            . "Nama          : " . $userName . "\n"
            . "Jabatan       : " . $userRole . "\n"
            . "Waktu TTD     : " . date('d/m/Y H:i:s') . "\n"
            . "Saldo Akhir   : Rp " . number_format($runningBalance, 0, ',', '.') . "\n"
            . "Status        : TERTANDA DIGITAL SAH (VERIFIED)";
        $signerQrCode = base64_encode(QrCode::format('svg')->size(70)->generate($signerPayload));

        $docPayload = "LEMBAR PENGESAHAN BUKU BESAR RESMI\n"
            . "==================================\n"
            . "Sistem        : PT Dumai Akuntansi ERP\n"
            . "Akun          : " . $account->code . " (" . $account->name . ")\n"
            . "Total Debit   : Rp " . number_format($totalDebit, 0, ',', '.') . "\n"
            . "Total Kredit  : Rp " . number_format($totalCredit, 0, ',', '.') . "\n"
            . "Saldo Akhir   : Rp " . number_format($runningBalance, 0, ',', '.') . "\n"
            . "Status        : LENGKAP & TERVERIFIKASI";
        $documentQrCode = base64_encode(QrCode::format('svg')->size(70)->generate($docPayload));

        $pdfData = [
            'account' => $account,
            'startDate' => date('d/m/Y', strtotime($startDate)),
            'endDate' => date('d/m/Y', strtotime($endDate)),
            'branchName' => $branchName,
            'beginningBalance' => $beginningBalance,
            'endingBalance' => $runningBalance,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'transactions' => $transactions,
            'userName' => $userName,
            'userRole' => $userRole,
            'ownerName' => 'Direksi & Owner PT Dumai',
            'signerQrCode' => $signerQrCode,
            'documentQrCode' => $documentQrCode,
        ];

        $pdf = Pdf::loadView('pdf.accounting_ledger', $pdfData);
        $pdf->setPaper('a4', 'portrait');

        $cleanAccCode = preg_replace('/[^A-Za-z0-9_-]/', '', $account->code);
        return $pdf->download('Buku_Besar_' . $cleanAccCode . '_' . date('Ymd', strtotime($startDate)) . '_' . date('Ymd', strtotime($endDate)) . '.pdf');
    }

    /**
     * Export Chart of Accounts (COA) PDF
     */
    public function exportCoaPdf(Request $request)
    {
        $q = $request->query('q');
        $type = $request->query('type');

        $query = Account::with('parent')
            ->when($q, function ($query, $q) {
                $query->where(function ($w) use ($q) {
                    $w->where('code', 'like', "%{$q}%")
                      ->orWhere('name', 'like', "%{$q}%")
                      ->orWhere('description', 'like', "%{$q}%");
                });
            })
            ->when($type && $type !== 'all', fn($query) => $query->where('type', $type))
            ->orderBy('code', 'asc');

        $rawAccounts = $query->get();
        $accounts = [];
        $counts = [
            'asset' => 0,
            'liability' => 0,
            'equity' => 0,
            'revenue' => 0,
            'cogs' => 0,
            'expense' => 0,
        ];

        foreach ($rawAccounts as $acc) {
            $bal = $this->calculateAccountBalance($acc);
            if (isset($counts[$acc->type])) {
                $counts[$acc->type]++;
            }

            $accounts[] = [
                'code' => $acc->code,
                'name' => $acc->name,
                'type' => $acc->type,
                'category' => $acc->category,
                'normal_balance' => $acc->normal_balance,
                'parent_id' => $acc->parent_id,
                'description' => $acc->description,
                'current_balance' => $bal,
                'is_active' => $acc->is_active,
            ];
        }

        $user = auth()->user();
        $userName = $user ? $user->name : 'Administrator';
        $userRole = ($user && $user->role) ? $user->role->name : 'Akuntan / Finance';

        $signerPayload = "TANDA TANGAN RESMI MASTER BAGAN AKUN (COA)\n"
            . "============================================\n"
            . "Nama          : " . $userName . "\n"
            . "Jabatan       : " . $userRole . "\n"
            . "Waktu TTD     : " . date('d/m/Y H:i:s') . "\n"
            . "Total Akun    : " . count($accounts) . " Akun\n"
            . "Status        : TERTANDA DIGITAL SAH (VERIFIED)";
        $signerQrCode = base64_encode(QrCode::format('svg')->size(70)->generate($signerPayload));

        $docPayload = "LEMBAR PENGESAHAN MASTER BAGAN AKUN (COA)\n"
            . "========================================\n"
            . "Sistem        : PT Dumai Akuntansi ERP\n"
            . "Total Akun    : " . count($accounts) . " Rekening\n"
            . "Status        : RESMI & TERDAFTAR";
        $documentQrCode = base64_encode(QrCode::format('svg')->size(70)->generate($docPayload));

        $pdfData = [
            'accounts' => $accounts,
            'counts' => $counts,
            'userName' => $userName,
            'userRole' => $userRole,
            'ownerName' => 'Direksi & Owner PT Dumai',
            'signerQrCode' => $signerQrCode,
            'documentQrCode' => $documentQrCode,
        ];

        $pdf = Pdf::loadView('pdf.accounting_coa', $pdfData);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('Bagan_Akun_COA_' . date('Ymd') . '.pdf');
    }

    /**
     * Export Financial Statements (Neraca Saldo, Neraca, Laba Rugi) PDF
     */
    public function exportFinancialStatementsPdf(Request $request)
    {
        $branchId = $request->query('branch_id');
        $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', now()->endOfMonth()->toDateString());

        // 1. Trial Balance Data
        $accounts = Account::where('is_active', true)->orderBy('code', 'asc')->get();
        $trialBalance = [];
        $totalAssets = 0;
        $totalLiabilities = 0;
        $totalEquity = 0;
        $totalRevenue = 0;
        $totalCogs = 0;
        $totalExpense = 0;

        foreach ($accounts as $acc) {
            $bal = $this->calculateAccountBalance($acc, $branchId, $endDate);
            $debit = 0;
            $credit = 0;

            if ($acc->normal_balance === 'debit') {
                if ($bal >= 0) $debit = $bal;
                else $credit = abs($bal);
            } else {
                if ($bal >= 0) $credit = $bal;
                else $debit = abs($bal);
            }

            if ($acc->type === 'asset') $totalAssets += $bal;
            elseif ($acc->type === 'liability') $totalLiabilities += $bal;
            elseif ($acc->type === 'equity') {
                if ($acc->normal_balance === 'debit') $totalEquity -= $bal;
                else $totalEquity += $bal;
            } elseif ($acc->type === 'revenue') {
                $totalRevenue += $this->calculateAccountMovement($acc, $branchId, $startDate, $endDate);
            } elseif ($acc->type === 'cogs') {
                $totalCogs += $this->calculateAccountMovement($acc, $branchId, $startDate, $endDate);
            } elseif ($acc->type === 'expense') {
                $totalExpense += $this->calculateAccountMovement($acc, $branchId, $startDate, $endDate);
            }

            if ($debit > 0 || $credit > 0) {
                $trialBalance[] = [
                    'code' => $acc->code,
                    'name' => $acc->name,
                    'type' => $acc->type,
                    'debit' => $debit,
                    'credit' => $credit,
                ];
            }
        }

        $grossProfit = $totalRevenue - $totalCogs;
        $netIncome = $grossProfit - $totalExpense;

        $branchName = 'Semua Cabang';
        if ($branchId && $branchId !== 'all') {
            $branchObj = Branch::find($branchId);
            if ($branchObj) $branchName = $branchObj->name;
        }

        $user = auth()->user();
        $userName = $user ? $user->name : 'Administrator';
        $userRole = ($user && $user->role) ? $user->role->name : 'Akuntan / Finance';

        $signerPayload = "TANDA TANGAN PENGESAHAN LAPORAN KEUANGAN\n"
            . "=========================================\n"
            . "Nama          : " . $userName . "\n"
            . "Jabatan       : " . $userRole . "\n"
            . "Waktu TTD     : " . date('d/m/Y H:i:s') . "\n"
            . "Laba Bersih   : Rp " . number_format($netIncome, 0, ',', '.') . "\n"
            . "Status        : TERTANDA DIGITAL SAH (VERIFIED)";
        $signerQrCode = base64_encode(QrCode::format('svg')->size(70)->generate($signerPayload));

        $docPayload = "LEMBAR PENGESAHAN LAPORAN KEUANGAN RESMI\n"
            . "========================================\n"
            . "Sistem        : PT Dumai Akuntansi ERP\n"
            . "Total Aktiva  : Rp " . number_format($totalAssets, 0, ',', '.') . "\n"
            . "Total Pasiva  : Rp " . number_format($totalLiabilities + $totalEquity, 0, ',', '.') . "\n"
            . "Laba Bersih   : Rp " . number_format($netIncome, 0, ',', '.') . "\n"
            . "Status        : AUDITED & FINAL";
        $documentQrCode = base64_encode(QrCode::format('svg')->size(70)->generate($docPayload));

        $pdfData = [
            'startDate' => date('d/m/Y', strtotime($startDate)),
            'endDate' => date('d/m/Y', strtotime($endDate)),
            'branchName' => $branchName,
            'trialBalance' => $trialBalance,
            'totalAssets' => $totalAssets,
            'totalLiabilities' => $totalLiabilities,
            'totalEquity' => $totalEquity,
            'totalRevenue' => $totalRevenue,
            'totalCogs' => $totalCogs,
            'totalExpense' => $totalExpense,
            'grossProfit' => $grossProfit,
            'netIncome' => $netIncome,
            'userName' => $userName,
            'userRole' => $userRole,
            'ownerName' => 'Direksi & Owner PT Dumai',
            'signerQrCode' => $signerQrCode,
            'documentQrCode' => $documentQrCode,
        ];

        $pdf = Pdf::loadView('pdf.accounting_financial_statement', $pdfData);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('Laporan_Keuangan_' . date('Ymd', strtotime($startDate)) . '_' . date('Ymd', strtotime($endDate)) . '.pdf');
    }
}
