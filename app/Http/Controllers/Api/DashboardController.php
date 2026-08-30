<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\ProductBranch;
use App\Models\StockMovement;
use App\Models\CashReconciliation;
use App\Models\StockOpname;
use Carbon\Carbon;

class DashboardController extends Controller
{
    // GET /analytics
    public function analytics(Request $request)
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->month;
        $thisYear = Carbon::now()->year;

        $query = Sale::where('status', 'completed');

        // Income
        $dailyIncome = (clone $query)->whereDate('date', $today)->sum('total_amount');
        $monthlyIncome = (clone $query)->whereMonth('date', $thisMonth)->whereYear('date', $thisYear)->sum('total_amount');
        $yearlyIncome = (clone $query)->whereYear('date', $thisYear)->sum('total_amount');

        // Low stock
        $lowStockQuery = ProductBranch::with(['product', 'branch'])->where('stock', '<=', 10)->orderBy('stock', 'asc')->limit(5);
        
        // Receivables (Piutang) - Sisa Utang Belum Lunas
        $outstandingReceivables = \App\Models\Receivable::whereIn('status', ['unpaid', 'partial'])
            ->selectRaw('SUM(amount_due - amount_paid) as total_outstanding')
            ->value('total_outstanding') ?? 0;

        // Returns (Retur) this month
        $monthlyReturns = \App\Models\ReturnTransaction::whereMonth('created_at', $thisMonth)
            ->whereYear('created_at', $thisYear)
            ->where('status', 'completed')
            ->sum('total_amount') ?? 0;

        // Expiring Batches (dalam 30 hari)
        $thirtyDaysFromNow = Carbon::now()->addDays(30);
        $expiringBatches = \App\Models\ProductBatch::with(['productBranch.product', 'productBranch.branch'])
            ->whereNotNull('expiration_date')
            ->where('qty', '>', 0)
            ->where('expiration_date', '<=', $thirtyDaysFromNow)
            ->orderBy('expiration_date', 'asc')
            ->limit(10)
            ->get();

        // Latest Stock Opname Discrepancy (Selisih)
        $latestOpname = \App\Models\StockOpname::where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->first();

        // FIFO: Dead Stock (Umur stok > 90 hari)
        $ninetyDaysAgo = Carbon::now()->subDays(90);
        $deadStock = \App\Models\ProductBatch::with(['productBranch.product', 'productBranch.branch'])
            ->where('qty', '>', 0)
            ->where('entry_date', '<=', $ninetyDaysAgo)
            ->orderBy('entry_date', 'asc')
            ->limit(10)
            ->get();

        // LIFO: New Stock (Masuk dalam 30 hari terakhir)
        $newStock = \App\Models\ProductBatch::with(['productBranch.product', 'productBranch.branch'])
            ->where('qty', '>', 0)
            ->where('entry_date', '>=', $thirtyDaysFromNow->copy()->subDays(60)) // 30 days ago since $thirtyDaysFromNow is now + 30
            ->orderBy('entry_date', 'desc')
            ->limit(10)
            ->get();

        // Purchase Orders (Pengadaan Bulan Ini & Pending PO)
        $monthlyPurchases = \App\Models\PurchaseOrder::whereMonth('date', $thisMonth)
            ->whereYear('date', $thisYear)
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount') ?? 0;

        $pendingPoCount = \App\Models\PurchaseOrder::where('status', 'pending')->count();

        // Discounts given in POS this month
        $monthlyDiscounts = (clone $query)->whereMonth('date', $thisMonth)
            ->whereYear('date', $thisYear)
            ->sum('discount') ?? 0;

        // Monthly Income Chart Data (12 Months)
        $monthlyIncomeChart = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthIncome = Sale::where('status', 'completed')
                ->whereMonth('date', $m)
                ->whereYear('date', $thisYear)
                ->sum('total_amount');
            $monthlyIncomeChart[] = (float)$monthIncome;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'low_stock' => $lowStockQuery->get(),
                'high_stock' => [],
                'income' => [
                    'daily' => $dailyIncome,
                    'monthly' => $monthlyIncome,
                    'yearly' => $yearlyIncome,
                ],
                'purchases' => [
                    'monthly' => (float)$monthlyPurchases,
                    'pending_count' => (int)$pendingPoCount,
                ],
                'discounts' => [
                    'monthly' => (float)$monthlyDiscounts,
                ],
                'receivables' => [
                    'outstanding' => (float)$outstandingReceivables,
                ],
                'returns' => [
                    'monthly' => (float)$monthlyReturns,
                ],
                'expiring_batches' => $expiringBatches,
                'dead_stock' => $deadStock,
                'new_stock' => $newStock,
                'latest_opname' => $latestOpname ? [
                    'id' => $latestOpname->id,
                    'total_system_qty' => $latestOpname->total_system_qty,
                    'total_actual_qty' => $latestOpname->total_actual_qty,
                    'total_discrepancy' => $latestOpname->total_discrepancy,
                    'date' => $latestOpname->date,
                ] : null,
                'recent_sales' => (clone $query)->with(['user', 'branch'])->orderBy('created_at', 'desc')->limit(5)->get(),
                'chart' => [
                    'monthly_income' => $monthlyIncomeChart
                ]
            ]
        ]);
    }

    // GET /sales-analytics
    public function salesAnalytics(Request $request)
    {
        $period = $request->query('period', 'monthly'); // daily, monthly, yearly
        $branchId = $request->query('branch_id', 'all');

        $query = Sale::where('status', 'completed');
        $pastQuery = Sale::where('status', 'completed');

        if ($branchId && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
            $pastQuery->where('branch_id', $branchId);
        }

        $now = Carbon::now();
        $chartData = [];

        if ($period === 'daily') {
            // Last 7 days
            $startDate = clone $now;
            $startDate->subDays(6)->startOfDay();
            
            $query->where('date', '>=', $startDate);
            
            $pastStartDate = clone $startDate;
            $pastStartDate->subDays(7);
            $pastEndDate = clone $now;
            $pastEndDate->subDays(7)->endOfDay();
            
            $pastQuery->whereBetween('date', [$pastStartDate, $pastEndDate]);

            for ($i = 6; $i >= 0; $i--) {
                $d = clone $now;
                $d->subDays($i);
                $dateStr = $d->format('Y-m-d');
                $dSales = (clone $query)->whereDate('date', $dateStr)->get();
                
                $revenue = $dSales->sum('total_amount');
                $cogs = 0;
                foreach ($dSales as $sale) {
                    $cogs += DB::table('sale_items')->where('sale_id', $sale->id)->sum(DB::raw('cost_price * qty'));
                }
                
                $chartData[] = [
                    'date' => $d->format('D, d M'),
                    'revenue' => (float)$revenue,
                    'profit' => (float)($revenue - $cogs)
                ];
            }
        } else if ($period === 'monthly') {
            // Last 6 months
            $startDate = clone $now;
            $startDate->subMonths(5)->startOfMonth();
            $query->where('date', '>=', $startDate);

            $pastStartDate = clone $startDate;
            $pastStartDate->subMonths(6);
            $pastEndDate = clone $now;
            $pastEndDate->subMonths(6)->endOfMonth();
            $pastQuery->whereBetween('date', [$pastStartDate, $pastEndDate]);

            for ($i = 5; $i >= 0; $i--) {
                $d = clone $now;
                $d->subMonths($i);
                $m = $d->month;
                $y = $d->year;
                
                $mSales = (clone $query)->whereMonth('date', $m)->whereYear('date', $y)->get();
                $revenue = $mSales->sum('total_amount');
                $cogs = 0;
                foreach ($mSales as $sale) {
                    $cogs += DB::table('sale_items')->where('sale_id', $sale->id)->sum(DB::raw('cost_price * qty'));
                }

                $chartData[] = [
                    'date' => $d->format('M Y'),
                    'revenue' => (float)$revenue,
                    'profit' => (float)($revenue - $cogs)
                ];
            }
        } else if ($period === 'yearly') {
            // Last 5 years
            $startDate = clone $now;
            $startDate->subYears(4)->startOfYear();
            $query->where('date', '>=', $startDate);

            $pastStartDate = clone $startDate;
            $pastStartDate->subYears(5);
            $pastEndDate = clone $now;
            $pastEndDate->subYears(5)->endOfYear();
            $pastQuery->whereBetween('date', [$pastStartDate, $pastEndDate]);

            for ($i = 4; $i >= 0; $i--) {
                $d = clone $now;
                $d->subYears($i);
                $y = $d->year;
                
                $mSales = (clone $query)->whereYear('date', $y)->get();
                $revenue = $mSales->sum('total_amount');
                $cogs = 0;
                foreach ($mSales as $sale) {
                    $cogs += DB::table('sale_items')->where('sale_id', $sale->id)->sum(DB::raw('cost_price * qty'));
                }

                $chartData[] = [
                    'date' => $d->format('Y'),
                    'revenue' => (float)$revenue,
                    'profit' => (float)($revenue - $cogs)
                ];
            }
        }

        $currentSales = $query->get();
        $pastSales = $pastQuery->get();

        $curCount = $currentSales->count();
        $curRevenue = $currentSales->sum('total_amount');
        
        $curCogs = 0;
        foreach ($currentSales as $s) {
            $curCogs += DB::table('sale_items')->where('sale_id', $s->id)->sum(DB::raw('cost_price * qty'));
        }
        $curProfit = $curRevenue - $curCogs;

        $pastCount = $pastSales->count();
        $pastRevenue = $pastSales->sum('total_amount');
        $pastCogs = 0;
        foreach ($pastSales as $s) {
            $pastCogs += DB::table('sale_items')->where('sale_id', $s->id)->sum(DB::raw('cost_price * qty'));
        }
        $pastProfit = $pastRevenue - $pastCogs;

        $calcGrowth = function($cur, $past) {
            if ($past == 0) return $cur > 0 ? 100 : 0;
            return round((($cur - $past) / $past) * 100, 1);
        };

        $aov = $curCount > 0 ? round($curRevenue / $curCount) : 0;
        $totalDiscount = (clone $query)->sum('discount') ?? 0;

        // Payment Methods Breakdown
        $paymentBreakdown = (clone $query)->select('payment_method', DB::raw('count(*) as count'), DB::raw('sum(total_amount) as total'))
            ->groupBy('payment_method')
            ->get();

        // Bank Accounts Revenue Breakdown
        $bankBreakdown = (clone $query)->whereNotNull('bank_account_id')
            ->select('bank_account_id', 'bank_name', DB::raw('count(*) as count'), DB::raw('sum(total_amount) as total'))
            ->groupBy('bank_account_id', 'bank_name')
            ->get();

        // Top 5 Best Selling Products
        $saleIds = $currentSales->pluck('id');
        $topProducts = [];
        if ($saleIds->isNotEmpty()) {
            $topProducts = DB::table('sale_items')
                ->join('product_branches', 'sale_items.product_branch_id', '=', 'product_branches.id')
                ->join('products', 'product_branches.product_id', '=', 'products.id')
                ->whereIn('sale_items.sale_id', $saleIds)
                ->select(
                    'products.id',
                    'products.name',
                    'products.sku',
                    DB::raw('SUM(sale_items.qty) as total_qty'),
                    DB::raw('SUM(sale_items.subtotal) as total_revenue')
                )
                ->groupBy('products.id', 'products.name', 'products.sku')
                ->orderByDesc('total_revenue')
                ->limit(5)
                ->get();
        }

        // Recent 5 Sales Transactions
        $recentTransactions = (clone $query)
            ->with(['user:id,name', 'customer:id,name', 'branch:id,name', 'bankAccount:id,bank_name,account_number,color'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $margin = $curRevenue > 0 ? round(($curProfit / $curRevenue) * 100, 1) : 0;
        $summary = [
            'sales' => ['value' => $curCount, 'growth' => $calcGrowth($curCount, $pastCount)],
            'revenue' => ['value' => (float)$curRevenue, 'growth' => $calcGrowth($curRevenue, $pastRevenue)],
            'profit' => ['value' => (float)$curProfit, 'growth' => $calcGrowth($curProfit, $pastProfit)],
            'aov' => ['value' => (float)$aov],
            'discount' => ['value' => (float)$totalDiscount],
            'margin' => $margin,
        ];

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'chart' => $chartData,
            'payment_breakdown' => $paymentBreakdown,
            'bank_breakdown' => $bankBreakdown,
            'top_products' => $topProducts,
            'recent_transactions' => $recentTransactions,
            'data' => [
                'kpi' => [
                    'revenue' => (float)$curRevenue,
                    'revenue_growth' => $calcGrowth($curRevenue, $pastRevenue),
                    'profit' => (float)$curProfit,
                    'profit_growth' => $calcGrowth($curProfit, $pastProfit),
                    'orders_count' => $curCount,
                    'orders_growth' => $calcGrowth($curCount, $pastCount),
                    'aov' => $aov,
                    'total_discount' => (float)$totalDiscount
                ],
                'summary' => $summary,
                'chart' => $chartData,
                'payment_breakdown' => $paymentBreakdown,
                'bank_breakdown' => $bankBreakdown,
                'top_products' => $topProducts,
                'recent_transactions' => $recentTransactions
            ]
        ]);
    }

    // GET /inventory
    public function inventory(Request $request)
    {
        $limit = $request->query('limit', 10);
        $page = $request->query('page', 1);

        $lowStock = ProductBranch::with(['product', 'branch'])->where('stock', '>', 0)->where('stock', '<=', 10)->orderBy('stock', 'asc')->limit(10)->get();
        $outOfStock = ProductBranch::with(['product', 'branch'])->where('stock', '<=', 0)->limit(10)->get();
        $paginated = StockMovement::with(['productBranch.product', 'productBranch.branch', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);

        // Map to include product_name directly for easy access on frontend
        $movements = $paginated->toArray();
        $movements['data'] = collect($paginated->items())->map(function ($movement) {
            $arr = $movement->toArray();
            $productBranch = $movement->productBranch;
            $arr['product_name'] = ($productBranch && $productBranch->product) ? $productBranch->product->name : '-';
            $arr['branch_name']  = ($productBranch && $productBranch->branch)  ? $productBranch->branch->name  : '-';
            return $arr;
        })->values()->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'low_stock' => $lowStock,
                'out_of_stock' => $outOfStock,
                'recent_movements' => $movements
            ]
        ]);
    }


    // GET /profit
    public function profit(Request $request = null)
    {
        $request = $request ?? request();
        $today = Carbon::today();
        $thisMonth = Carbon::now()->month;
        $thisYear = Carbon::now()->year;
        $branchId = $request->query('branch_id');

        // Base Query
        $salesQuery = Sale::where('status', 'completed');
        $expenseQuery = \App\Models\PettyCash::query();

        if ($branchId && $branchId !== 'all') {
            $salesQuery->where('branch_id', $branchId);
            $expenseQuery->where('branch_id', $branchId);
        }

        // Today
        $salesToday = (clone $salesQuery)->whereDate('date', $today)->get();
        $revenueToday = (float) $salesToday->sum('total_amount');
        $cogsToday = 0;
        foreach ($salesToday as $sale) {
            $cogsToday += DB::table('sale_items')->where('sale_id', $sale->id)->sum(DB::raw('cost_price * qty'));
        }
        $expenseToday = (float) (clone $expenseQuery)->whereDate('date', $today)->sum('amount');
        $grossProfitToday = $revenueToday - $cogsToday;
        $netProfitToday = $grossProfitToday - $expenseToday;

        // This Month
        $salesMonth = (clone $salesQuery)->whereMonth('date', $thisMonth)->whereYear('date', $thisYear)->get();
        $revenueMonth = (float) $salesMonth->sum('total_amount');
        $cogsMonth = 0;
        foreach ($salesMonth as $sale) {
            $cogsMonth += DB::table('sale_items')->where('sale_id', $sale->id)->sum(DB::raw('cost_price * qty'));
        }
        $expenseMonth = (float) (clone $expenseQuery)->whereMonth('date', $thisMonth)->whereYear('date', $thisYear)->sum('amount');
        $grossProfitMonth = $revenueMonth - $cogsMonth;
        $netProfitMonth = $grossProfitMonth - $expenseMonth;

        // All Time
        $salesAll = (clone $salesQuery)->get();
        $revenueAll = (float) $salesAll->sum('total_amount');
        $cogsAll = 0;
        foreach ($salesAll as $sale) {
            $cogsAll += DB::table('sale_items')->where('sale_id', $sale->id)->sum(DB::raw('cost_price * qty'));
        }
        $expenseAll = (float) (clone $expenseQuery)->sum('amount');
        $grossProfitAll = $revenueAll - $cogsAll;
        $netProfitAll = $grossProfitAll - $expenseAll;

        // Last 6 months chart data
        $chartCategories = [];
        $chartGross = [];
        $chartExpenses = [];
        $chartNet = [];
        $now = Carbon::now();
        for ($i = 5; $i >= 0; $i--) {
            $d = clone $now;
            $d->subMonths($i);
            $mSales = (clone $salesQuery)->whereMonth('date', $d->month)->whereYear('date', $d->year)->get();
            $rev = (float) $mSales->sum('total_amount');
            $cg = 0;
            foreach ($mSales as $sale) {
                $cg += DB::table('sale_items')->where('sale_id', $sale->id)->sum(DB::raw('cost_price * qty'));
            }
            $exp = (float) (clone $expenseQuery)->whereMonth('date', $d->month)->whereYear('date', $d->year)->sum('amount');
            $gross = $rev - $cg;
            $net = $gross - $exp;

            $chartCategories[] = $d->format('M Y');
            $chartGross[] = (float) $gross;
            $chartExpenses[] = (float) $exp;
            $chartNet[] = (float) $net;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'revenue_today' => $revenueToday,
                'cogs_today' => (float) $cogsToday,
                'expense_today' => $expenseToday,
                'gross_profit_today' => $grossProfitToday,
                'profit_today' => $netProfitToday,

                'revenue_this_month' => $revenueMonth,
                'cogs_this_month' => (float) $cogsMonth,
                'expense_this_month' => $expenseMonth,
                'gross_profit_this_month' => $grossProfitMonth,
                'profit_this_month' => $netProfitMonth,

                'total_revenue' => $revenueAll,
                'total_cogs' => (float) $cogsAll,
                'total_expense' => $expenseAll,
                'total_gross_profit' => $grossProfitAll,
                'total_profit' => $netProfitAll,
                'margin' => $revenueAll > 0 ? round(($netProfitAll / $revenueAll) * 100, 1) : 0,

                'chart_data' => [
                    'categories' => $chartCategories,
                    'series' => $chartNet,
                    'series_gross' => $chartGross,
                    'series_expenses' => $chartExpenses,
                ]
            ]
        ]);
    }

    public function audit(Request $request)
    {
        $itemsPerPage = (int) $request->query('itemsPerPage', 10);
        $search = $request->query('search');
        $event = $request->query('event');

        $query = DB::table('activity_log')
            ->leftJoin('users', 'activity_log.causer_id', '=', 'users.id')
            ->select('activity_log.*', 'users.name as user_name');

        if ($event) {
            $query->where('activity_log.description', $event);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('activity_log.description', 'like', "%{$search}%")
                  ->orWhere('activity_log.subject_type', 'like', "%{$search}%")
                  ->orWhere('users.name', 'like', "%{$search}%");
            });
        }

        $logs = (clone $query)->orderBy('activity_log.created_at', 'desc')->paginate($itemsPerPage);

        // Summary counts
        $totalLogs = DB::table('activity_log')->count();
        $totalCreated = DB::table('activity_log')->where('description', 'created')->count();
        $totalUpdated = DB::table('activity_log')->where('description', 'updated')->count();
        $totalDeleted = DB::table('activity_log')->where('description', 'deleted')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'logs' => $logs->items(),
                'total' => $logs->total(),
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'summary' => [
                    'total' => $totalLogs,
                    'created' => $totalCreated,
                    'updated' => $totalUpdated,
                    'deleted' => $totalDeleted,
                ]
            ]
        ]);
    }
}
