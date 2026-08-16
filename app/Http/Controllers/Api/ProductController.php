<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $itemsPerPage = $request->query('itemsPerPage', 15);
        $page = $request->query('page', 1);

        $query = Product::with(['category']);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $query->orderBy('created_at', 'desc');

        if ($itemsPerPage == -1) {
            $products = $query->get();
            $paginated = null;
        } else {
            $paginated = $query->paginate($itemsPerPage, ['*'], 'page', $page);
            $products = $paginated->items();
        }

        $response = [
            'data' => $products,
        ];

        if ($paginated) {
            $response['current_page'] = $paginated->currentPage();
            $response['last_page'] = $paginated->lastPage();
            $response['per_page'] = $paginated->perPage();
            $response['total'] = $paginated->total();
        }

        return response()->json($response);
    }

    public function store(Request $request)
    {
        if (!request()->user()->can('Produk Create')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255|unique:products,sku',
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:Aktif,Nonaktif',
            'stock_method' => 'nullable|string|in:fifo,lifo,fefo',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'brand' => 'nullable|string|max:255',
            'barcode' => 'nullable|string|max:255',
            'unit' => 'nullable|string|max:255',
            'weight' => 'nullable|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'is_returnable' => 'nullable|boolean',
            'tax_type' => 'nullable|string|max:255',
        ]);

        $data = $request->only([
            'name', 'sku', 'category_id', 'description', 'stock_method',
            'brand', 'barcode', 'unit', 'weight', 'length', 'width', 'height', 'is_returnable', 'tax_type'
        ]);
        $data['status'] = $request->status ?? 'Aktif';

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product = Product::create($data);

        return response()->json(['message' => 'Master Produk berhasil ditambahkan', 'product' => $product->load('category')], 201);
    }

    public function show(Product $product)
    {
        $product->load(['category', 'productBranches']);
        return response()->json($product);
    }

    public function update(Request $request, Product $product)
    {
        if (!request()->user()->can('Produk Write')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255|unique:products,sku,' . $product->id,
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:Aktif,Nonaktif',
            'stock_method' => 'nullable|string|in:fifo,lifo,fefo',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'brand' => 'nullable|string|max:255',
            'barcode' => 'nullable|string|max:255',
            'unit' => 'nullable|string|max:255',
            'weight' => 'nullable|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'is_returnable' => 'nullable|boolean',
            'tax_type' => 'nullable|string|max:255',
        ]);

        $data = $request->only([
            'name', 'sku', 'category_id', 'description', 'status', 'stock_method',
            'brand', 'barcode', 'unit', 'weight', 'length', 'width', 'height', 'is_returnable', 'tax_type'
        ]);

        if ($request->hasFile('image')) {
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        } elseif ($request->image === null && $request->has('image')) {
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = null;
        }

        $product->update($data);

        return response()->json(['message' => 'Master Produk berhasil diperbarui', 'product' => $product->load('category')]);
    }

    public function destroy(Product $product)
    {
        if (!request()->user()->can('Produk Delete')) {
            abort(403, 'Unauthorized action.');
        }

        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }
        $product->delete();
        return response()->json(['message' => 'Master Produk berhasil dihapus']);
    }

    public function import(Request $request)
    {
        if (!request()->user()->can('Produk Create')) {
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
            
            // Format: Nama Produk, SKU, Kategori (Wajib), Deskripsi, Merek, Barcode, Satuan, Berat, Bisa Retur
            if (isset($row[0]) && trim($row[0]) !== '' && isset($row[1]) && trim($row[1]) !== '' && isset($row[2]) && trim($row[2]) !== '') {
                // Terintegrasi: Cari kategori berdasarkan nama, jika tidak ada, buat baru
                $category = \App\Models\Category::firstOrCreate(
                    ['name' => trim($row[2])],
                    ['description' => 'Kategori dibuat otomatis dari import produk']
                );
                $categoryId = $category->id;

                \App\Models\Product::updateOrCreate(
                    ['sku' => trim($row[1])],
                    [
                        'name' => trim($row[0]),
                        'category_id' => $categoryId,
                        'description' => isset($row[3]) ? trim($row[3]) : null,
                        'brand' => isset($row[4]) ? trim($row[4]) : null,
                        'barcode' => isset($row[5]) ? trim($row[5]) : null,
                        'unit' => isset($row[6]) && trim($row[6]) !== '' ? trim($row[6]) : 'Pcs',
                        'weight' => isset($row[7]) && is_numeric(trim($row[7])) ? trim($row[7]) : null,
                        'is_returnable' => isset($row[8]) && strtolower(trim($row[8])) === 'false' ? false : true,
                        'status' => 'Aktif',
                    ]
                );
                $count++;
            }
        }
        
        fclose($handle);
        return response()->json(['message' => "$count Produk berhasil diimpor"]);
    }
}
