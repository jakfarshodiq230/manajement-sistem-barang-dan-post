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
            // Get all movements up to the end date
            $movements = StockMovement::where('product_branch_id', $pb->id)
                ->where('created_at', '<=', $end)
                ->get();

            // Calculate initial stock (before start date)
            $initialStock = 0;
            $movementsBeforeStart = $movements->where('created_at', '<', $start);
            foreach ($movementsBeforeStart as $mov) {
                if ($mov->type === 'in') {
                    $initialStock += $mov->quantity;
                } else if ($mov->type === 'out') {
                    $initialStock -= $mov->quantity;
                }
            }

            // Group movements during the period by date
            $movementsDuring = $movements->where('created_at', '>=', $start);
            
            $dailyData = [];
            foreach ($dateRanges as $date) {
                $dailyData[$date] = ['in' => 0, 'out' => 0];
            }

            $currentStock = $initialStock;
            
            foreach ($movementsDuring as $mov) {
                $movDate = $mov->created_at->format('Y-m-d');
                if (isset($dailyData[$movDate])) {
                    if ($mov->type === 'in') {
                        $dailyData[$movDate]['in'] += $mov->quantity;
                        $currentStock += $mov->quantity;
                    } else if ($mov->type === 'out') {
                        $dailyData[$movDate]['out'] += $mov->quantity;
                        $currentStock -= $mov->quantity;
                    }
                }
            }

            $report[] = [
                'id' => $pb->id,
                'kode_barang' => $pb->product->sku,
                'nama_barang' => $pb->product->name,
                'kategori' => $pb->product->category->name ?? '-',
                'cabang' => $pb->branch->name ?? '-',
                'harga_barang' => $pb->price,
                'stok_awal' => $initialStock,
                'harian' => $dailyData,
                'sisa_stok' => $currentStock, // Should match $pb->stock conceptually if up to today
                'nilai_persediaan_akhir' => $currentStock * $pb->price,
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

        if ($itemsPerPage == -1) {
            $productBranches = $query->get();
            $paginated = null;
        } else {
            $paginated = $query->paginate($itemsPerPage);
            $productBranches = collect($paginated->items());
        }

        $report = $productBranches->map(function ($pb) {
            return [
                'id' => $pb->id,
                'kode_barang' => $pb->product->sku,
                'nama_barang' => $pb->product->name,
                'kategori' => $pb->product->category->name ?? '-',
                'cabang' => $pb->branch->name ?? '-',
                'sisa_stok' => $pb->stock,
                'harga_jual' => $pb->price,
                'nilai_aset' => $pb->stock * $pb->price,
            ];
        });

        $response = ['data' => $report];
        
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

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        // Calculate total qty sold per product_branch
        $salesData = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween('sales.created_at', [$start, $end]);

        if ($branchId) {
            $salesData->where('sales.branch_id', $branchId);
        }

        $salesData = $salesData->select('sale_items.product_branch_id', DB::raw('SUM(sale_items.qty) as total_sold'))
            ->groupBy('sale_items.product_branch_id')
            ->get()
            ->keyBy('product_branch_id');

        $search = $request->query('search');
        $page = (int) $request->query('page', 1);
        $itemsPerPage = (int) $request->query('itemsPerPage', 15);

        // Get all product branches to include those that didn't sell (Slow moving)
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
        $productBranches = $query->get();

        $report = [];
        foreach ($productBranches as $pb) {
            $totalSold = isset($salesData[$pb->id]) ? (int)$salesData[$pb->id]->total_sold : 0;
            
            $report[] = [
                'id' => $pb->id,
                'kode_barang' => $pb->product->sku,
                'nama_barang' => $pb->product->name,
                'kategori' => $pb->product->category->name ?? '-',
                'cabang' => $pb->branch->name ?? '-',
                'terjual' => $totalSold,
                'sisa_stok' => $pb->stock
            ];
        }

        // Sort descending by total sold
        usort($report, function($a, $b) {
            return $b['terjual'] <=> $a['terjual'];
        });

        // Determine fast vs slow. Let's say top 30% are fast, bottom 30% are slow, middle are average.
        // Or simple: top 10 = fast, bottom 10 = slow.
        $totalItems = count($report);
        foreach ($report as $index => &$item) {
            if ($totalItems > 0) {
                $percentile = ($index + 1) / $totalItems;
                if ($percentile <= 0.3) {
                    $item['kategori_kecepatan'] = 'Fast Moving';
                } else if ($percentile >= 0.7 || $item['terjual'] == 0) {
                    $item['kategori_kecepatan'] = 'Slow Moving';
                } else {
                    $item['kategori_kecepatan'] = 'Average';
                }
            } else {
                $item['kategori_kecepatan'] = 'Average';
            }
        }

        $total = count($report);
        
        if ($itemsPerPage != -1) {
            $report = array_slice($report, ($page - 1) * $itemsPerPage, $itemsPerPage);
        }

        return response()->json([
            'data' => $report,
            'total' => $total,
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
            // Expiring soon first
            $query->whereNotNull('expiration_date')->orderBy('expiration_date', 'asc');
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

        $report = collect($batches)->map(function ($batch) use ($now) {
            $productBranch = $batch->productBranch;
            $product = $productBranch ? $productBranch->product : null;
            $branch = $productBranch ? $productBranch->branch : null;

            // Calculate age in days
            $entryDate = Carbon::parse($batch->entry_date);
            $ageDays = $entryDate->diffInDays($now);

            // Calculate days to expire
            $daysToExpire = null;
            if ($batch->expiration_date) {
                $expDate = Carbon::parse($batch->expiration_date);
                $daysToExpire = $now->diffInDays($expDate, false); // negative if already expired
            }

            return [
                'id' => $batch->id,
                'kode_barang' => $product ? $product->sku : '-',
                'nama_barang' => $product ? $product->name : '-',
                'kategori' => ($product && $product->category) ? $product->category->name : '-',
                'cabang' => $branch ? $branch->name : '-',
                'qty_sisa' => $batch->qty,
                'harga_beli' => $batch->cost_price,
                'nilai_aset' => $batch->qty * $batch->cost_price,
                'tanggal_masuk' => $batch->entry_date,
                'umur_stok_hari' => $ageDays,
                'tanggal_expired' => $batch->expiration_date,
                'sisa_hari_expired' => $daysToExpire,
            ];
        });

        $response = [
            'data' => $report,
            'total' => $total,
        ];

        if ($paginator) {
            $response['current_page'] = $paginator->currentPage();
            $response['last_page'] = $paginator->lastPage();
            $response['per_page'] = $paginator->perPage();
        }

        return response()->json($response);
    }
}
