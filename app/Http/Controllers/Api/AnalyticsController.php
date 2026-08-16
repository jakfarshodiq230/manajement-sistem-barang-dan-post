<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\ProductBranch;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Use user's active branch or global if no branch is set
        $branchId = null;
        if ($user->active_role_id) {
            $assignment = \DB::table('model_has_roles')
                ->where('model_id', $user->id)
                ->where('model_type', get_class($user))
                ->where('role_id', $user->active_role_id)
                ->first();
            if ($assignment && $assignment->branch_id) {
                $branchId = $assignment->branch_id;
            }
        }
        
        // Define stock thresholds
        $lowStockThreshold = 10;
        $highStockThreshold = 100;
        
        // Query builder for stock
        $stockQuery = ProductBranch::with(['product', 'branch']);
        if ($branchId) {
            $stockQuery->where('branch_id', $branchId);
        }
        
        $lowStockProducts = (clone $stockQuery)
            ->where('stock', '<', $lowStockThreshold)
            ->where('stock', '>', 0) // Avoid counting discontinued? Keep 0 if they matter
            ->orderBy('stock', 'asc')
            ->limit(10)
            ->get();
            
        $highStockProducts = (clone $stockQuery)
            ->where('stock', '>', $highStockThreshold)
            ->orderBy('stock', 'desc')
            ->limit(10)
            ->get();
            
        // Query builder for sales
        $salesQuery = Sale::query();
        if ($branchId) {
            $salesQuery->where('branch_id', $branchId);
        }
        
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $startOfYear = Carbon::now()->startOfYear();
        
        $incomeToday = (clone $salesQuery)
            ->whereDate('date', $today)
            ->sum('total_amount');
            
        $incomeThisMonth = (clone $salesQuery)
            ->whereDate('date', '>=', $startOfMonth)
            ->sum('total_amount');
            
        $incomeThisYear = (clone $salesQuery)
            ->whereDate('date', '>=', $startOfYear)
            ->sum('total_amount');
            
        // Recent Sales
        $recentSales = (clone $salesQuery)
            ->with(['branch', 'user'])
            ->orderBy('date', 'desc')
            ->limit(5)
            ->get();
            
        // Chart Data (Monthly Income for this year)
        $monthlyIncomeRaw = (clone $salesQuery)
            ->whereYear('date', Carbon::now()->year)
            ->selectRaw('MONTH(date) as month, SUM(total_amount) as total')
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();
            
        $monthlyIncomeChart = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthlyIncomeChart[] = $monthlyIncomeRaw[$i] ?? 0;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'low_stock' => $lowStockProducts,
                'high_stock' => $highStockProducts,
                'income' => [
                    'daily' => $incomeToday,
                    'monthly' => $incomeThisMonth,
                    'yearly' => $incomeThisYear,
                ],
                'recent_sales' => $recentSales,
                'chart' => [
                    'monthly_income' => $monthlyIncomeChart
                ]
            ]
        ]);
    }
    
    public function sales(Request $request)
    {
        $branchId = $this->getBranchId($request->user());
        
        $salesQuery = Sale::query();
        $saleItemsQuery = \App\Models\SaleItem::query()->join('sales', 'sales.id', '=', 'sale_items.sale_id');
        
        if ($branchId) {
            $salesQuery->where('branch_id', $branchId);
            $saleItemsQuery->where('sales.branch_id', $branchId);
        }
        
        $today = Carbon::today();
        
        $dailySalesCount = (clone $salesQuery)->whereDate('date', $today)->count();
        $dailyRevenue = (clone $salesQuery)->whereDate('date', $today)->sum('total_amount');
        
        $topProducts = (clone $saleItemsQuery)
            ->join('product_branches', 'product_branches.id', '=', 'sale_items.product_branch_id')
            ->join('products', 'products.id', '=', 'product_branches.product_id')
            ->select('products.name', \DB::raw('SUM(sale_items.qty) as total_qty'), \DB::raw('SUM(sale_items.subtotal) as total_revenue'))
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_qty', 'desc')
            ->limit(10)
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => [
                'daily_sales_count' => $dailySalesCount,
                'daily_revenue' => $dailyRevenue,
                'top_products' => $topProducts
            ]
        ]);
    }
    
    public function inventory(Request $request)
    {
        $branchId = $this->getBranchId($request->user());
        
        $stockQuery = ProductBranch::with(['product', 'branch']);
        if ($branchId) {
            $stockQuery->where('branch_id', $branchId);
        }
        
        $lowStock = (clone $stockQuery)->where('stock', '<', 10)->where('stock', '>', 0)->orderBy('stock', 'asc')->get();
        $outOfStock = (clone $stockQuery)->where('stock', '<=', 0)->get();
        
        $movementsQuery = \DB::table('stock_movements')
            ->join('product_branches', 'product_branches.id', '=', 'stock_movements.product_branch_id')
            ->join('products', 'products.id', '=', 'product_branches.product_id')
            ->select('stock_movements.*', 'products.name as product_name')
            ->orderBy('stock_movements.created_at', 'desc');
            
        if ($branchId) {
            $movementsQuery->where('product_branches.branch_id', $branchId);
        }
        
        $recentMovements = $movementsQuery->limit(20)->get();
        
        return response()->json([
            'success' => true,
            'data' => [
                'low_stock' => $lowStock,
                'out_of_stock' => $outOfStock,
                'recent_movements' => $recentMovements
            ]
        ]);
    }
    
    public function profit(Request $request)
    {
        $branchId = $this->getBranchId($request->user());
        
        $saleItemsQuery = \App\Models\SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'completed');
            
        if ($branchId) {
            $saleItemsQuery->where('sales.branch_id', $branchId);
        }
        
        // Calculate Profit: SUM( (price - cost_price) * qty )
        $profitQuery = (clone $saleItemsQuery)->selectRaw('SUM((sale_items.price - sale_items.cost_price) * sale_items.qty) as total_profit, SUM(sale_items.subtotal) as total_revenue')->first();
        
        $profitToday = (clone $saleItemsQuery)->whereDate('sales.date', Carbon::today())->selectRaw('SUM((sale_items.price - sale_items.cost_price) * sale_items.qty) as profit')->first()->profit ?? 0;
        
        $profitThisMonth = (clone $saleItemsQuery)->whereDate('sales.date', '>=', Carbon::now()->startOfMonth())->selectRaw('SUM((sale_items.price - sale_items.cost_price) * sale_items.qty) as profit')->first()->profit ?? 0;
        
        return response()->json([
            'success' => true,
            'data' => [
                'total_profit' => $profitQuery->total_profit ?? 0,
                'total_revenue' => $profitQuery->total_revenue ?? 0,
                'profit_today' => $profitToday,
                'profit_this_month' => $profitThisMonth
            ]
        ]);
    }
    
    public function audit(Request $request)
    {
        $logs = \DB::table('activity_log')
            ->leftJoin('users', 'users.id', '=', 'activity_log.causer_id')
            ->select('activity_log.*', 'users.name as user_name')
            ->orderBy('activity_log.created_at', 'desc')
            ->limit(50)
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => [
                'logs' => $logs
            ]
        ]);
    }
    
    private function getBranchId($user)
    {
        if ($user->active_role_id) {
            $assignment = \DB::table('model_has_roles')
                ->where('model_id', $user->id)
                ->where('model_type', get_class($user))
                ->where('role_id', $user->active_role_id)
                ->first();
            if ($assignment && $assignment->branch_id) {
                return $assignment->branch_id;
            }
        }
        return null;
    }
}
