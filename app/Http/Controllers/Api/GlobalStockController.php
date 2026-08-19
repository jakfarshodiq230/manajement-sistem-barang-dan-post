<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class GlobalStockController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $categoryId = $request->query('category_id');
        $itemsPerPage = (int) $request->query('itemsPerPage', 15);

        // For the owner/admin to view global stock
        // We load products with their branches
        $query = Product::with(['category', 'productBranches.branch']);
        
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%");
            });
        }
        
        if ($itemsPerPage == -1) {
            $products = $query->get();
            $paginated = null;
        } else {
            $paginated = $query->paginate($itemsPerPage);
            $products = collect($paginated->items());
        }

        $totalGudangGlobal = 0;
        $totalTokoGlobal = 0;

        $data = $products->map(function ($product) use (&$totalGudangGlobal, &$totalTokoGlobal) {
            $totalStoreStock = 0;
            $totalWarehouseStock = 0;
            $branches = [];

            foreach ($product->productBranches as $pb) {
                if ($pb->branch) {
                    if ($pb->branch->type === 'warehouse') {
                        $totalWarehouseStock += (int) $pb->stock;
                    } else {
                        $totalStoreStock += (int) $pb->stock;
                    }

                    $branches[] = [
                        'branch_name' => $pb->branch->name,
                        'branch_type' => $pb->branch->type,
                        'stock' => (int) $pb->stock,
                    ];
                }
            }

            $totalGudangGlobal += $totalWarehouseStock;
            $totalTokoGlobal += $totalStoreStock;

            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'brand' => $product->brand ?? '-',
                'category_name' => $product->category ? $product->category->name : '-',
                'total_store_stock' => $totalStoreStock,
                'total_warehouse_stock' => $totalWarehouseStock,
                'total_overall' => $totalStoreStock + $totalWarehouseStock,
                'branches' => $branches,
            ];
        });

        // Summary
        $summary = [
            'total_sku' => $paginated ? $paginated->total() : count($products),
            'total_warehouse' => $totalGudangGlobal,
            'total_store' => $totalTokoGlobal,
            'total_overall' => $totalGudangGlobal + $totalTokoGlobal,
        ];

        $response = [
            'success' => true,
            'data' => $data,
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
}
