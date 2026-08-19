<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductBranch;
use App\Models\StockMovement;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Report 1: Laporan Riwayat Stok
     * Columns: Barang, Harga, Stok Awal, (Per Tanggal: Masuk, Keluar), Sisa Stok, Nilai
     */
    public function stockHistory(Request $request)
    {
        $startDate = $request->query('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', Carbon::now()->endOfMonth()->toDateString());
        $branchId = $request->query('branch_id');

        $search = $request->query('search');
        $itemsPerPage = $request->query('itemsPerPage', 15);

        $query = ProductBranch::with(['product.category', 'branch']);
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        if ($search) {
            $query->whereHas('product', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }
        
        if ($itemsPerPage == -1) {
            $productBranches = $query->get();
            $paginated = null;
        } else {
            $paginated = $query->paginate($itemsPerPage);
            $productBranches = $paginated->items();
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $report = [];
        
        // Generate list of dates between start and end
        $dateRanges = [];
        $current = $start->copy();
        while ($current->lte($end)) {
            $dateStr = $current->format('Y-m-d');
            $dateRanges[] = $dateStr;
            $current->addDay();
        }

        foreach ($productBranches as $pb) {
            // Calculate movements after the period to find ending stock at $end
            $movementsAfter = StockMovement::where('product_branch_id', $pb->id)
                ->where('created_at', '>', $end)
                ->get();

            $netAfter = 0;
            foreach ($movementsAfter as $m) {
                if ($m->type === 'in') {
                    $netAfter += $m->quantity;
                } else if ($m->type === 'out') {
                    $netAfter -= $m->quantity;
                }
            }
            $stockAtEnd = (int) $pb->stock - $netAfter;

            // Group movements during the period by date
            $movementsDuring = StockMovement::where('product_branch_id', $pb->id)
                ->whereBetween('created_at', [$start, $end])
                ->get();
            
            $dailyData = [];
            foreach ($dateRanges as $date) {
                $dailyData[$date] = ['in' => 0, 'out' => 0];
            }

            $netDuring = 0;
            foreach ($movementsDuring as $mov) {
                $movDate = $mov->created_at->format('Y-m-d');
                if (isset($dailyData[$movDate])) {
                    if ($mov->type === 'in') {
                        $dailyData[$movDate]['in'] += (int) $mov->quantity;
                        $netDuring += (int) $mov->quantity;
                    } else if ($mov->type === 'out') {
                        $dailyData[$movDate]['out'] += (int) $mov->quantity;
                        $netDuring -= (int) $mov->quantity;
                    }
                }
            }

            $initialStock = $stockAtEnd - $netDuring;
            if ($initialStock < 0) {
                $initialStock = 0;
            }

            $report[] = [
                'id' => $pb->id,
                'kode_barang' => $pb->product->sku ?? '-',
                'nama_barang' => $pb->product->name ?? 'Produk',
                'brand' => $pb->product->brand ?? '-',
                'kategori' => $pb->product->category->name ?? '-',
                'cabang' => $pb->branch->name ?? '-',
                'harga_barang' => (float) ($pb->price ?? 0),
                'stok_awal' => (int) $initialStock,
                'harian' => $dailyData,
                'sisa_stok' => (int) $stockAtEnd,
                'nilai_persediaan_akhir' => (float) ($stockAtEnd * ($pb->price ?? 0)),
            ];
        }

        $response = [
            'dates' => $dateRanges,
            'data' => $report
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
     * Report 2: Laporan Stok Saat Ini
     */
    public function currentStock(Request $request)
    {
        $branchId = $request->query('branch_id');
        $search = $request->query('search');
        $itemsPerPage = $request->query('itemsPerPage', 15);
        
        $query = ProductBranch::with(['product.category', 'branch']);
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($search) {
            $query->whereHas('product', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $allQuery = clone $query;
        $allProducts = $allQuery->get();
        $totalStock = $allProducts->sum('stock');
        $totalAssetValue = $allProducts->sum(function($pb) { return $pb->stock * $pb->price; });
        $lowStockCount = $allProducts->filter(function($pb) { return $pb->stock <= 5; })->count();

        $summary = [
            'total_items' => $allProducts->count(),
            'total_stock' => (int) $totalStock,
            'total_asset_value' => (float) $totalAssetValue,
            'total_low_stock' => $lowStockCount,
        ];

        if ($itemsPerPage == -1) {
            $productBranches = $allProducts;
            $paginated = null;
        } else {
            $paginated = $query->paginate($itemsPerPage);
            $productBranches = collect($paginated->items());
        }

        $report = $productBranches->map(function ($pb) {
            return [
                'id' => $pb->id,
                'kode_barang' => $pb->product->sku ?? '-',
                'nama_barang' => $pb->product->name ?? '-',
                'brand' => $pb->product->brand ?? '-',
                'kategori' => $pb->product->category->name ?? '-',
                'cabang' => $pb->branch->name ?? '-',
                'sisa_stok' => (int) $pb->stock,
                'harga_jual' => (float) $pb->price,
                'nilai_aset' => (float) ($pb->stock * $pb->price),
            ];
        });

        $response = [
            'data' => $report,
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

    /**
     * Report 3: Produk Fast Slow Moving
     */
    public function fastSlowMoving(Request $request)
    {
        $startDate = $request->query('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', Carbon::now()->endOfMonth()->toDateString());
        $branchId = $request->query('branch_id');
        $categoryId = $request->query('category_id');
        $speedFilter = $request->query('speed_status'); // fast, medium, slow, dead
        $search = $request->query('search');
        $page = (int) $request->query('page', 1);
        $itemsPerPage = (int) $request->query('itemsPerPage', 15);

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        // Calculate total qty and revenue sold per product_branch
        $salesQuery = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween('sales.created_at', [$start, $end])
            ->where('sales.status', 'completed');

        if ($branchId) {
            $salesQuery->where('sales.branch_id', $branchId);
        }

        $salesData = $salesQuery->select(
            'sale_items.product_branch_id',
            DB::raw('SUM(sale_items.qty) as total_sold'),
            DB::raw('SUM(sale_items.subtotal) as total_revenue')
        )
        ->groupBy('sale_items.product_branch_id')
        ->get()
        ->keyBy('product_branch_id');

        // Query product branches with product & category info
        $query = ProductBranch::with(['product.category', 'branch']);
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        if ($categoryId) {
            $query->whereHas('product', function($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }
        if ($search) {
            $query->whereHas('product', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%");
            });
        }

        $productBranches = $query->get();

        $allReport = [];
        $summary = [
            'total_items' => 0,
            'fast_moving' => 0,
            'medium_moving' => 0,
            'slow_moving' => 0,
            'dead_stock' => 0,
            'total_sales_revenue' => 0,
            'total_idle_asset_value' => 0, // Nilai modal dead stock & slow moving
        ];

        foreach ($productBranches as $pb) {
            $soldInfo = $salesData->get($pb->id);
            $totalSold = $soldInfo ? (int) $soldInfo->total_sold : 0;
            $totalRevenue = $soldInfo ? (float) $soldInfo->total_revenue : 0;
            $costPrice = (float) ($pb->cost_price ?? 0);
            $stock = (int) ($pb->stock ?? 0);
            $assetValue = $stock * $costPrice;

            // Standar Klasifikasi FSN (Fast, Slow, Non-Moving/Dead Stock)
            if ($totalSold >= 10) {
                $speedCategory = 'Fast Moving';
                $summary['fast_moving']++;
            } elseif ($totalSold >= 2) {
                $speedCategory = 'Medium Moving';
                $summary['medium_moving']++;
            } elseif ($totalSold === 1) {
                $speedCategory = 'Slow Moving';
                $summary['slow_moving']++;
                $summary['total_idle_asset_value'] += $assetValue;
            } else {
                // totalSold === 0
                $speedCategory = $stock > 0 ? 'Dead Stock' : 'Non-Active';
                if ($stock > 0) {
                    $summary['dead_stock']++;
                    $summary['total_idle_asset_value'] += $assetValue;
                } else {
                    $summary['slow_moving']++;
                }
            }

            $summary['total_sales_revenue'] += $totalRevenue;

            $itemData = [
                'id' => $pb->id,
                'kode_barang' => $pb->product->sku ?? '-',
                'nama_barang' => $pb->product->name ?? 'Produk',
                'brand' => $pb->product->brand ?? '-',
                'kategori' => $pb->product->category->name ?? '-',
                'cabang' => $pb->branch->name ?? '-',
                'terjual' => $totalSold,
                'total_omset' => $totalRevenue,
                'sisa_stok' => $stock,
                'cost_price' => $costPrice,
                'nilai_aset' => $assetValue,
                'kategori_kecepatan' => $speedCategory,
            ];

            // Apply speed status filter if present
            if ($speedFilter) {
                if ($speedFilter === 'fast' && $speedCategory !== 'Fast Moving') continue;
                if ($speedFilter === 'medium' && $speedCategory !== 'Medium Moving') continue;
                if ($speedFilter === 'slow' && $speedCategory !== 'Slow Moving') continue;
                if ($speedFilter === 'dead' && $speedCategory !== 'Dead Stock') continue;
            }

            $allReport[] = $itemData;
        }

        $summary['total_items'] = count($productBranches);

        // Sort descending by total sold, then by stock
        usort($allReport, function($a, $b) {
            if ($b['terjual'] === $a['terjual']) {
                return $b['sisa_stok'] <=> $a['sisa_stok'];
            }
            return $b['terjual'] <=> $a['terjual'];
        });

        $totalFiltered = count($allReport);

        if ($itemsPerPage != -1) {
            $pagedData = array_slice($allReport, ($page - 1) * $itemsPerPage, $itemsPerPage);
        } else {
            $pagedData = $allReport;
        }

        return response()->json([
            'data' => $pagedData,
            'total' => $totalFiltered,
            'summary' => $summary,
        ]);
    }

    /**
     * Report 4: Analisis Usia Stok (FIFO, FEFO, LIFO)
     */
    public function stockAging(Request $request)
    {
        $filter = $request->query('filter', 'all'); // 'all', 'fifo', 'fefo', 'lifo'
        $branchId = $request->query('branch_id');
        $search = $request->query('search');
        $itemsPerPage = $request->query('itemsPerPage', 15);
        $page = $request->query('page', 1);

        $query = \App\Models\ProductBatch::with(['productBranch.product.category', 'productBranch.branch'])
            ->where('qty', '>', 0); // Only batches with remaining stock

        if ($branchId) {
            $query->whereHas('productBranch', function($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        if ($search) {
            $query->whereHas('productBranch.product', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Apply filters & sorting based on methodology
        if ($filter === 'fifo') {
            // Oldest stock first (Dead stock)
            $query->orderBy('entry_date', 'asc');
        } elseif ($filter === 'lifo') {
            // Newest stock first (Recent arrivals)
            $query->orderBy('entry_date', 'desc');
        } elseif ($filter === 'fefo') {
            // Expiring soon first - strictly only products with valid expiration date
            $query->whereNotNull('expiration_date')
                ->where('expiration_date', '!=', '')
                ->where('expiration_date', '!=', '0000-00-00')
                ->orderBy('expiration_date', 'asc');
        } else {
            // Default sort by entry_date desc
            $query->orderBy('entry_date', 'desc');
        }

        if ($itemsPerPage == -1) {
            $paginator = null;
            $batches = $query->get();
            $total = $batches->count();
        } else {
            $paginator = $query->paginate($itemsPerPage);
            $batches = $paginator->items();
            $total = $paginator->total();
        }

        $now = Carbon::now();

        // Calculate summary across all active batches
        $allBatches = (clone $query)->get();
        $totalQty = $allBatches->sum('qty');
        $totalAssetValue = $allBatches->sum(function($b) { return $b->qty * $b->cost_price; });
        $expiringSoonCount = $allBatches->filter(function($b) use ($now) {
            if (!$b->expiration_date || $b->expiration_date === '0000-00-00') return false;
            $exp = Carbon::parse($b->expiration_date);
            $days = $now->diffInDays($exp, false);
            return $days <= 30; // 30 days or already expired
        })->count();

        $summary = [
            'total_batches' => $allBatches->count(),
            'total_qty' => (int) $totalQty,
            'total_asset_value' => (float) $totalAssetValue,
            'expiring_soon' => $expiringSoonCount,
        ];

        $report = collect($batches)->map(function ($batch) use ($now) {
            $productBranch = $batch->productBranch;
            $product = $productBranch ? $productBranch->product : null;
            $branch = $productBranch ? $productBranch->branch : null;

            // Calculate age in integer days and hours
            $entryDate = Carbon::parse($batch->entry_date);
            $diffAge = $entryDate->diff($now);
            $days = (int) $diffAge->days;
            $hours = (int) $diffAge->h;
            $minutes = (int) $diffAge->i;

            $ageParts = [];
            if ($days > 0) {
                $ageParts[] = "{$days} hari";
            }
            if ($hours > 0) {
                $ageParts[] = "{$hours} jam";
            }
            if (empty($ageParts)) {
                $ageParts[] = $minutes > 0 ? "{$minutes} menit" : "Baru saja";
            }
            $umurFormat = implode(' ', $ageParts);

            // Calculate days & hours to expire
            $daysToExpire = null;
            $expiredFormat = null;
            if ($batch->expiration_date && $batch->expiration_date !== '0000-00-00') {
                $expDate = Carbon::parse($batch->expiration_date);
                $isPast = $now->greaterThan($expDate);
                $diffExp = $now->diff($expDate);
                $expDays = (int) $diffExp->days;
                $expHours = (int) $diffExp->h;
                $expMinutes = (int) $diffExp->i;

                $expParts = [];
                if ($expDays > 0) {
                    $expParts[] = "{$expDays} hari";
                }
                if ($expHours > 0) {
                    $expParts[] = "{$expHours} jam";
                }
                if (empty($expParts)) {
                    $expParts[] = $expMinutes > 0 ? "{$expMinutes} menit" : "Hari ini";
                }
                $expLabel = implode(' ', $expParts);
                $expiredFormat = $isPast ? "Expired ({$expLabel} lalu)" : "Sisa {$expLabel}";
                $daysToExpire = $isPast ? -$expDays : $expDays;
            }

            return [
                'id' => $batch->id,
                'kode_barang' => $product ? $product->sku : '-',
                'nama_barang' => $product ? $product->name : '-',
                'brand' => $product ? ($product->brand ?? '-') : '-',
                'kategori' => ($product && $product->category) ? $product->category->name : '-',
                'cabang' => $branch ? $branch->name : '-',
                'qty_sisa' => (int) $batch->qty,
                'harga_beli' => (float) $batch->cost_price,
                'nilai_aset' => (float) ($batch->qty * $batch->cost_price),
                'tanggal_masuk' => $batch->entry_date,
                'umur_stok_hari' => $days,
                'umur_sisa_jam' => $hours,
                'umur_format' => $umurFormat,
                'tanggal_expired' => $batch->expiration_date,
                'sisa_hari_expired' => $daysToExpire,
                'expired_format' => $expiredFormat,
            ];
        });

        $response = [
            'data' => $report,
            'total' => $total,
            'summary' => $summary,
        ];

        if ($paginator) {
            $response['current_page'] = $paginator->currentPage();
            $response['last_page'] = $paginator->lastPage();
            $response['per_page'] = $paginator->perPage();
        }

        return response()->json($response);
    }
}
