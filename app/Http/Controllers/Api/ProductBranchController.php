<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductBranch;
use Illuminate\Http\Request;

class ProductBranchController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductBranch::with(['product.category', 'branch']);
        
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->whereHas('product', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }
        
        if ($request->has('category_id') && $request->category_id != '') {
            $categoryId = $request->category_id;
            $query->whereHas('product', function($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }
        
        if ($request->has('has_stock') && $request->has_stock == 'true') {
            $query->where('stock', '>', 0);
        }
        
        if ($request->has('paginate') && $request->paginate == 'true') {
            $perPage = $request->input('per_page', $request->input('itemsPerPage', 6));
            return response()->json($query->paginate($perPage));
        }
        
        if ($request->has('itemsPerPage')) {
            $itemsPerPage = $request->input('itemsPerPage', 15);
            $page = $request->input('page', 1);
            
            if ($itemsPerPage == -1) {
                $data = $query->get();
                return response()->json(['data' => $data]);
            }
            
            $paginated = $query->paginate($itemsPerPage, ['*'], 'page', $page);
            return response()->json([
                'data' => $paginated->items(),
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ]);
        }
        
        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        if (!request()->user()->can('Inventori Cabang Create')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'branch_id' => 'required|exists:branches,id',
            'cost_price' => 'required|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
            'other_fees' => 'nullable|numeric|min:0',
            'min_nego_price' => 'nullable|numeric|min:0',
        ]);

        // Ensure unique combination
        $exists = ProductBranch::where('product_id', $request->product_id)
            ->where('branch_id', $request->branch_id)
            ->first();

        if ($exists) {
            return response()->json(['message' => 'Produk ini sudah ada di cabang tersebut'], 422);
        }

        $data = $request->only(['product_id', 'branch_id', 'cost_price', 'price', 'tax_percentage', 'other_fees', 'min_nego_price']);
        $data['tax_percentage'] = $data['tax_percentage'] ?? 0;
        $data['other_fees'] = $data['other_fees'] ?? 0;
        $data['min_nego_price'] = $data['min_nego_price'] ?? 0;
        $data['stock'] = 0; // initial stock is 0

        $productBranch = ProductBranch::create($data);

        return response()->json([
            'message' => 'Harga produk untuk cabang berhasil ditambahkan',
            'product_branch' => $productBranch->load(['product', 'branch'])
        ], 201);
    }

    public function show(ProductBranch $productBranch)
    {
        $productBranch->load(['product', 'branch', 'stockMovements', 'productBatches']);
        return response()->json($productBranch);
    }

    public function update(Request $request, ProductBranch $productBranch)
    {
        if (!request()->user()->can('Inventori Cabang Write')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'cost_price' => 'required|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
            'other_fees' => 'nullable|numeric|min:0',
            'min_nego_price' => 'nullable|numeric|min:0',
        ]);

        $data = $request->only(['cost_price', 'price', 'tax_percentage', 'other_fees', 'min_nego_price']);
        
        if ($request->has('tax_percentage')) {
            $data['tax_percentage'] = $request->tax_percentage ?? 0;
        }
        if ($request->has('other_fees')) {
            $data['other_fees'] = $request->other_fees ?? 0;
        }
        if ($request->has('min_nego_price')) {
            $data['min_nego_price'] = $request->min_nego_price ?? 0;
        }

        $productBranch->update($data);

        return response()->json([
            'message' => 'Detail harga cabang berhasil diperbarui',
            'product_branch' => $productBranch->load(['product', 'branch'])
        ]);
    }

    public function destroy(ProductBranch $productBranch)
    {
        if (!request()->user()->can('Inventori Cabang Delete')) {
            abort(403, 'Unauthorized action.');
        }

        $productBranch->delete();
        return response()->json(['message' => 'Produk dihapus dari cabang']);
    }

    public function import(Request $request)
    {
        if (!request()->user()->can('Inventori Cabang Create')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), "r");
        
        $header = true;
        $count = 0;
        
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if ($header) {
                $header = false;
                continue; // Skip header row
            }
            
            // Format: SKU Produk, Nama Cabang, Harga Modal, Harga Jual, Harga Nego, Pajak (%), Biaya Lainnya
            if (isset($row[0]) && trim($row[0]) !== '' && isset($row[1]) && trim($row[1]) !== '') {
                $product = \App\Models\Product::where('sku', trim($row[0]))->first();
                $branch = \App\Models\Branch::where('name', trim($row[1]))->first();

                if ($product && $branch) {
                    ProductBranch::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'branch_id' => $branch->id,
                        ],
                        [
                            'cost_price' => isset($row[2]) ? (float) trim($row[2]) : 0,
                            'price' => isset($row[3]) ? (float) trim($row[3]) : 0,
                            'min_nego_price' => isset($row[4]) ? (float) trim($row[4]) : 0,
                            'tax_percentage' => isset($row[5]) ? (float) trim($row[5]) : 0,
                            'other_fees' => isset($row[6]) ? (float) trim($row[6]) : 0,
                        ]
                    );
                    $count++;
                }
            }
        }
        
        fclose($handle);
        return response()->json(['message' => "$count data inventori cabang berhasil diimpor"]);
    }

    public function batchDetail($batchId)
    {
        $batch = \App\Models\ProductBatch::with('productBranch.product')->findOrFail($batchId);
        return response()->json($batch);
    }

    public function scanBatch($batchId)
    {
        $batch = \App\Models\ProductBatch::with('productBranch.product')->findOrFail($batchId);
        
        if ($batch->qty <= 0) {
            return response()->json(['message' => 'Stok batch ini sudah habis (0).'], 400);
        }

        return response()->json([
            'batch' => $batch,
            'product_branch' => $batch->productBranch
        ]);
    }

    public function updateBatchPrice(Request $request, $batchId)
    {
        if (!request()->user()->can('Inventori Cabang Write')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'price' => 'required|numeric|min:0',
            'min_nego_price' => 'nullable|numeric|min:0',
        ]);

        $batch = \App\Models\ProductBatch::findOrFail($batchId);
        
        $batch->price = $request->price;
        $batch->min_nego_price = $request->min_nego_price ?? 0;
        $batch->save();

        return response()->json([
            'message' => 'Harga batch berhasil diperbarui',
            'batch' => $batch
        ]);
    }
}
