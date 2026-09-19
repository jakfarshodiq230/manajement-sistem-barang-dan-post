<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductBranch;
use Illuminate\Http\Request;

class ProductBranchController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductBranch::with(['product.category', 'product.oriPromos', 'branch', 'productBatches']);
        
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
            'ori_discount_percent' => 'nullable|numeric|min:0|max:100',
            'ori_cashback_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        // Ensure unique combination
        $exists = ProductBranch::where('product_id', $request->product_id)
            ->where('branch_id', $request->branch_id)
            ->first();

        if ($exists) {
            return response()->json(['message' => 'Produk ini sudah ada di cabang tersebut'], 422);
        }

        $data = $request->only(['product_id', 'branch_id', 'cost_price', 'price', 'tax_percentage', 'other_fees', 'min_nego_price', 'ori_discount_percent', 'ori_cashback_percent']);
        $data['tax_percentage'] = $data['tax_percentage'] ?? 0;
        $data['other_fees'] = $data['other_fees'] ?? 0;
        $data['min_nego_price'] = $data['min_nego_price'] ?? 0;
        $data['ori_discount_percent'] = $data['ori_discount_percent'] ?? 0;
        $data['ori_cashback_percent'] = $data['ori_cashback_percent'] ?? 0;
        $data['stock'] = 0; // initial stock is 0

        $productBranch = ProductBranch::create($data);

        return response()->json([
            'message' => 'Harga produk untuk cabang berhasil ditambahkan',
            'product_branch' => $productBranch->load(['product', 'branch'])
        ], 201);
    }

    public function show(ProductBranch $productBranch)
    {
        $productBranch->load(['product.category', 'product.oriPromos', 'branch', 'stockMovements', 'productBatches']);
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
            'ori_discount_percent' => 'nullable|numeric|min:0|max:100',
            'ori_cashback_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $data = $request->only(['cost_price', 'price', 'tax_percentage', 'other_fees', 'min_nego_price', 'ori_discount_percent', 'ori_cashback_percent']);
        
        if ($request->has('tax_percentage')) {
            $data['tax_percentage'] = $request->tax_percentage ?? 0;
        }
        if ($request->has('other_fees')) {
            $data['other_fees'] = $request->other_fees ?? 0;
        }
        if ($request->has('min_nego_price')) {
            $data['min_nego_price'] = $request->min_nego_price ?? 0;
        }
        if ($request->has('ori_discount_percent')) {
            $data['ori_discount_percent'] = $request->ori_discount_percent ?? 0;
        }
        if ($request->has('ori_cashback_percent')) {
            $data['ori_cashback_percent'] = $request->ori_cashback_percent ?? 0;
        }

        $productBranch->update($data);

        return response()->json([
            'message' => 'Detail harga cabang berhasil diperbarui',
            'product_branch' => $productBranch->load(['product', 'branch'])
        ]);
    }

    public function importInitialTemplate(Request $request)
    {
        // $branchId = $request->query('branch_id');
        // $branchName = 'Semua Produk';
        // if ($branchId) {
        //     $branch = \App\Models\Branch::find($branchId);
        //     if ($branch) $branchName = $branch->name;
        // }
        
        $products = \App\Models\Product::orderBy('name')->get();
        
        $csvContent = "SKU (Wajib),Nama Produk,Kategori,Satuan,Qty Stok Fisik (Wajib),Harga Modal (Wajib),Harga Jual (Wajib),Harga Nego Minimum\n";
        
        foreach ($products as $p) {
            // Escape possible commas in names/categories
            $sku = str_replace('"', '""', $p->sku);
            $name = str_replace('"', '""', $p->name);
            $category = $p->category ? str_replace('"', '""', $p->category->name) : '';
            $unit = str_replace('"', '""', $p->unit);
            
            $csvContent .= "\"{$sku}\",\"{$name}\",\"{$category}\",\"{$unit}\",0,0,0,0\n";
        }
        
        if ($products->count() === 0) {
            $csvContent .= "\"CONTOH-SKU-001\",\"Produk Contoh (Master Barang Kosong)\",\"Umum\",\"Pcs\",10,100000,120000,110000\n";
        }
        
        return response()->json([
            'csv' => $csvContent
        ]);
    }

    public function importInitialStock(Request $request)
    {
        if (!request()->user()->can('Inventori Cabang Create')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
            'branch_id' => 'required|exists:branches,id',
        ]);

        $file = $request->file('file');
        $branchId = $request->input('branch_id');
        $handle = fopen($file->getRealPath(), "r");
        
        $header = true;
        $count = 0;
        
        \DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle, 4000, ",")) !== FALSE) {
                if ($header) {
                    $header = false;
                    continue; // Skip header row
                }
                
                // Format CSV: SKU, Nama Produk, Kategori, Satuan, Qty Stok, Harga Modal, Harga Jual, Harga Nego Minimum
                if (isset($row[0]) && trim($row[0]) !== '') {
                    $sku = trim($row[0]);
                    $qty = isset($row[4]) && is_numeric(trim($row[4])) ? (int)trim($row[4]) : 0;
                    $cost = isset($row[5]) && is_numeric(trim($row[5])) ? (float)trim($row[5]) : 0;
                    $price = isset($row[6]) && is_numeric(trim($row[6])) ? (float)trim($row[6]) : $cost;
                    $minNego = isset($row[7]) && is_numeric(trim($row[7])) ? (float)trim($row[7]) : $price;
                    
                    if ($qty > 0 || $price > 0 || $cost > 0) {
                        $product = \App\Models\Product::where('sku', $sku)->first();
                        
                        if ($product) {
                            $pb = \App\Models\ProductBranch::firstOrCreate(
                                ['product_id' => $product->id, 'branch_id' => $branchId],
                                ['cost_price' => $cost, 'price' => $price, 'min_nego_price' => $minNego, 'stock' => 0]
                            );
                            
                            // Update price if imported price is different
                            if ($pb->price != $price || $pb->cost_price != $cost || $pb->min_nego_price != $minNego) {
                                $pb->price = $price;
                                $pb->cost_price = $cost;
                                $pb->min_nego_price = $minNego;
                                $pb->save();
                            }
                            
                            if ($qty > 0) {
                                $batchDate = now()->toDateString();
                                $batchNumber = 'MIGRASI-AWAL-' . date('YmdHis') . '-' . $product->id;
                                
                                $batch = \App\Models\ProductBatch::create([
                                    'product_branch_id' => $pb->id,
                                    'batch_number' => $batchNumber,
                                    'qty' => $qty,
                                    'cost_price' => $cost,
                                    'price' => $price,
                                    'min_nego_price' => $minNego,
                                    'entry_date' => $batchDate
                                ]);
                                
                                $pb->stock += $qty;
                                $pb->save();
                                
                                // Create stock movement
                                \App\Models\StockMovement::create([
                                    'product_branch_id' => $pb->id,
                                    'user_id' => auth()->id(),
                                    'type' => 'in',
                                    'quantity' => $qty,
                                    'unit_cost' => $cost,
                                    'reference_type' => 'Migrasi Stok Awal',
                                    'reference_id' => $batch->id,
                                    'notes' => 'Stok Awal dari Migrasi Sistem (CSV Import)'
                                ]);
                            }
                            $count++;
                        }
                    }
                }
            }
            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            fclose($handle);
            return response()->json(['message' => 'Gagal mengimpor: ' . $e->getMessage()], 500);
        }
        
        fclose($handle);
        return response()->json(['message' => "$count data inventori cabang berhasil diimpor"]);
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
        $user = $request->user() ?: auth()->user();
        if ($user && !$user->can('Inventori Cabang Write') && !$user->can('manage all')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'cost_price' => 'nullable|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'min_nego_price' => 'nullable|numeric|min:0',
            'apply_to_all_batches' => 'nullable|boolean',
        ]);

        $batch = \App\Models\ProductBatch::findOrFail($batchId);
        
        if ($request->has('cost_price')) {
            $batch->cost_price = (float) $request->cost_price;
        }
        $batch->price = (float) $request->price;
        $batch->min_nego_price = $request->min_nego_price ? (float) $request->min_nego_price : 0;
        $batch->save();

        $pb = $batch->productBranch;
        if ($pb) {
            if ($request->apply_to_all_batches) {
                \App\Models\ProductBatch::where('product_branch_id', $pb->id)->update([
                    'cost_price' => $batch->cost_price,
                    'price' => $batch->price,
                    'min_nego_price' => $batch->min_nego_price,
                ]);
            }
            
            // Sync ProductBranch default price and HPP
            $activeBatch = $pb->productBatches()->where('qty', '>', 0)->orderBy('id', 'asc')->first();
            if ($activeBatch && $activeBatch->id === $batch->id) {
                if ($request->has('cost_price')) {
                    $pb->cost_price = $batch->cost_price;
                }
                $pb->price = $batch->price;
                $pb->min_nego_price = $batch->min_nego_price;
                $pb->save();
            } elseif ($request->apply_to_all_batches || $pb->price == 0) {
                if ($request->has('cost_price')) {
                    $pb->cost_price = $batch->cost_price;
                }
                $pb->price = $batch->price;
                $pb->min_nego_price = $batch->min_nego_price;
                $pb->save();
            }
        }

        return response()->json([
            'message' => 'Harga modal (HPP) & harga jual batch berhasil diperbarui',
            'batch' => $batch
        ]);
    }
}
