<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;

class AccountingReportService
{
    /**
     * Get aggregate balances for all accounts up to a specific date.
     * This avoids the N+1 query problem by doing a single GROUP BY query.
     */
    public function getAccountBalances($branchId, $endDate)
    {
        return DB::table('journal_entry_items')
            ->join('journal_entries', 'journal_entry_items.journal_entry_id', '=', 'journal_entries.id')
            ->select('account_id', DB::raw('SUM(debit) as total_debit'), DB::raw('SUM(credit) as total_credit'))
            ->when($branchId, fn($q) => $q->where('journal_entries.branch_id', $branchId))
            ->when($endDate, fn($q) => $q->whereDate('journal_entries.entry_date', '<=', $endDate))
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');
    }

    /**
     * Get aggregate movements for all accounts within a date range.
     */
    public function getAccountMovements($branchId, $startDate, $endDate)
    {
        return DB::table('journal_entry_items')
            ->join('journal_entries', 'journal_entry_items.journal_entry_id', '=', 'journal_entries.id')
            ->select('account_id', DB::raw('SUM(debit) as total_debit'), DB::raw('SUM(credit) as total_credit'))
            ->when($branchId, fn($q) => $q->where('journal_entries.branch_id', $branchId))
            ->when($startDate, fn($q) => $q->whereDate('journal_entries.entry_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('journal_entries.entry_date', '<=', $endDate))
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');
    }

    /**
     * Calculate current balance using pre-fetched aggregations.
     */
    public function calculateBalance($account, $balances)
    {
        $agg = $balances->get($account->id);
        $debit = $agg ? (float) $agg->total_debit : 0;
        $credit = $agg ? (float) $agg->total_credit : 0;
        $opening = (float) ($account->opening_balance ?? 0);

        if ($account->normal_balance === 'debit') {
            return $opening + ($debit - $credit);
        } else {
            return $opening + ($credit - $debit);
        }
    }

    /**
     * Calculate period movement using pre-fetched aggregations.
     */
    public function calculateMovement($account, $movements)
    {
        $agg = $movements->get($account->id);
        $debit = $agg ? (float) $agg->total_debit : 0;
        $credit = $agg ? (float) $agg->total_credit : 0;

        if ($account->normal_balance === 'debit') {
            return $debit - $credit;
        } else {
            return $credit - $debit;
        }
    }

    public function getOverviewMetrics($branchId, $startDate, $endDate)
    {
        $balances = $this->getAccountBalances($branchId, $endDate);
        $movements = $this->getAccountMovements($branchId, $startDate, $endDate);

        $accounts = Account::where('is_active', true)->get();

        $totalAssets = 0;
        $totalLiabilities = 0;
        $totalEquity = 0;
        $totalRevenue = 0;
        $totalCogs = 0;
        $totalExpenses = 0;

        foreach ($accounts as $acc) {
            $bal = $this->calculateBalance($acc, $balances);
            $mov = $this->calculateMovement($acc, $movements);

            switch ($acc->type) {
                case 'asset':
                    $totalAssets += $bal;
                    break;
                case 'liability':
                    $totalLiabilities += $bal;
                    break;
                case 'equity':
                    if ($acc->normal_balance === 'debit') {
                        $totalEquity -= $bal;
                    } else {
                        $totalEquity += $bal;
                    }
                    break;
                case 'revenue':
                    $totalRevenue += $mov;
                    break;
                case 'cogs':
                    $totalCogs += $mov;
                    break;
                case 'expense':
                    $totalExpenses += $mov;
                    break;
            }
        }

        $netProfit = $totalRevenue - $totalCogs - $totalExpenses;
        $totalJournalsCount = JournalEntry::when($branchId, fn($q) => $q->where('branch_id', $branchId))->count();

        return [
            'total_assets' => round($totalAssets, 2),
            'total_liabilities' => round($totalLiabilities, 2),
            'total_equity' => round($totalEquity, 2),
            'total_revenue' => round($totalRevenue, 2),
            'total_cogs' => round($totalCogs, 2),
            'total_expenses' => round($totalExpenses, 2),
            'net_profit' => round($netProfit, 2),
            'total_journals_count' => $totalJournalsCount,
            'is_balance_sheet_balanced' => abs($totalAssets - ($totalLiabilities + $totalEquity + $netProfit)) < 1.0,
        ];
    }
}
