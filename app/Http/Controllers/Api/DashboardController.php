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
                    'monthly_income' => []
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

        return response()->json([
            'summary' => [
                'sales' => ['value' => $curCount, 'growth' => $calcGrowth($curCount, $pastCount)],
                'revenue' => ['value' => $curRevenue, 'growth' => $calcGrowth($curRevenue, $pastRevenue)],
                'profit' => ['value' => $curProfit, 'growth' => $calcGrowth($curProfit, $pastProfit)],
            ],
            'chart' => $chartData
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
    public function profit()
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->month;
        $thisYear = Carbon::now()->year;

        // Today
        $salesToday = Sale::where('status', 'completed')->whereDate('date', $today)->get();
        $revenueToday = $salesToday->sum('total_amount');
        $cogsToday = 0;
        foreach ($salesToday as $sale) {
            $cogsToday += DB::table('sale_items')->where('sale_id', $sale->id)->sum(DB::raw('cost_price * qty'));
        }

        // This Month
        $salesMonth = Sale::where('status', 'completed')->whereMonth('date', $thisMonth)->whereYear('date', $thisYear)->get();
        $revenueMonth = $salesMonth->sum('total_amount');
        $cogsMonth = 0;
        foreach ($salesMonth as $sale) {
            $cogsMonth += DB::table('sale_items')->where('sale_id', $sale->id)->sum(DB::raw('cost_price * qty'));
        }

        // All Time
        $salesAll = Sale::where('status', 'completed')->get();
        $revenueAll = $salesAll->sum('total_amount');
        $cogsAll = 0;
        foreach ($salesAll as $sale) {
            $cogsAll += DB::table('sale_items')->where('sale_id', $sale->id)->sum(DB::raw('cost_price * qty'));
        }

        // Last 6 months chart data
        $chartData = [];
        $now = Carbon::now();
        for ($i = 5; $i >= 0; $i--) {
            $d = clone $now;
            $d->subMonths($i);
            $mSales = Sale::where('status', 'completed')->whereMonth('date', $d->month)->whereYear('date', $d->year)->get();
            $revenue = $mSales->sum('total_amount');
            $cogs = 0;
            foreach ($mSales as $sale) {
                $cogs += DB::table('sale_items')->where('sale_id', $sale->id)->sum(DB::raw('cost_price * qty'));
            }
            $chartData['categories'][] = $d->format('M Y');
            $chartData['series'][] = $revenue - $cogs;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'profit_today' => $revenueToday - $cogsToday,
                'profit_this_month' => $revenueMonth - $cogsMonth,
                'total_revenue' => $revenueAll,
                'total_profit' => $revenueAll - $cogsAll,
                'chart_data' => $chartData
            ]
        ]);
    }

    public function audit(Request $request)
    {
        $itemsPerPage = $request->query('itemsPerPage', 10);

        // Fetch activity logs using Spatie Activitylog with pagination
        $logs = DB::table('activity_log')
            ->leftJoin('users', 'activity_log.causer_id', '=', 'users.id')
            ->select('activity_log.*', 'users.name as user_name')
            ->orderBy('activity_log.created_at', 'desc')
            ->paginate($itemsPerPage);

        return response()->json([
            'success' => true,
            'data' => [
                'logs' => $logs->items(),
                'total' => $logs->total(),
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
            ]
        ]);
    }
}
