<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function getFilters()
    {
        $brands = Product::whereNotNull('brand')->where('brand', '!=', '')->distinct()->pluck('brand');
        $types = Product::whereNotNull('type')->where('type', '!=', '')->distinct()->pluck('type');
        return response()->json([
            'brands' => $brands,
            'types' => $types
        ]);
    }

    public function index(Request $request)
    {
        $search = $request->query('search');
        $itemsPerPage = $request->query('itemsPerPage', 15);
        $page = $request->query('page', 1);

        $query = Product::with(['category', 'productBranches.branch']);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($request->has('category_id') && $request->category_id !== 'all' && $request->category_id !== '') {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('brand') && $request->brand !== 'all' && $request->brand !== '') {
            $query->where('brand', $request->brand);
        }

        if ($request->has('type') && $request->type !== 'all' && $request->type !== '') {
            $query->where('type', $request->type);
        }

        if ($request->has('status') && $request->status !== 'all' && $request->status !== '') {
            $query->where('status', $request->status);
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
        if (!request()->user()->can('Data Produk & Barang Create') && !request()->user()->can('Produk Create')) {
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
        if (!request()->user()->can('Data Produk & Barang Write') && !request()->user()->can('Produk Write')) {
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
        if (!request()->user()->can('Data Produk & Barang Delete') && !request()->user()->can('Produk Delete')) {
            abort(403, 'Unauthorized action.');
        }

        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }
        $product->delete();
        return response()->json(['message' => 'Master Produk berhasil dihapus']);
    }

    public function importTemplate(Request $request)
    {
        $csvContent = "SKU (Wajib),Nama Produk (Wajib),Kategori (Wajib),Barcode,Merek,Satuan,Berat (g),Panjang (cm),Lebar (cm),Tinggi (cm),Metode Stok (fifo/fefo/lifo),Status (Aktif/Nonaktif),Deskripsi,Bisa Retur (Ya/Tidak),Diskon Ori (%),Cashback Ori (%)\n";
        $csvContent .= "SKU-001,Aki GS Astra Hybrid NS60,Otomotif,8991234567890,GS Astra,Pcs,15000,24,13,20,fifo,Aktif,Aki mobil hybrid,Ya,10,0\n";
        $csvContent .= "SKU-002,Aqua Botol 600ml,Minuman,8999999999999,Danone,Karton,15000,40,30,25,fefo,Aktif,Air mineral botol 600ml isi 24,Ya,0,5\n";
        
        return response($csvContent, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="Template_Master_Data_Produk.csv"',
        ]);
    }

    public function import(Request $request)
    {
        if (!$request->user()->can('Data Produk & Barang Create') && !$request->user()->can('Produk Create')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('file');
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
                
                // Format CSV: 
                // 0: SKU
                // 1: Nama Produk
                // 2: Kategori
                // 3: Barcode
                // 4: Merek
                // 5: Satuan
                // 6: Berat
                // 7: Panjang
                // 8: Lebar
                // 9: Tinggi
                // 10: Metode Stok
                // 11: Status
                // 12: Deskripsi
                // 13: Bisa Retur
                // 14: Diskon Ori (%)
                // 15: Cashback Ori (%)
                
                if (isset($row[0]) && trim($row[0]) !== '' && isset($row[1]) && trim($row[1]) !== '') {
                    
                    // Kategori opsional, jika tidak diisi masukkan ke kategori "Umum"
                    $catName = isset($row[2]) && trim($row[2]) !== '' ? trim($row[2]) : 'Umum';
                    $category = \App\Models\Category::firstOrCreate(
                        ['name' => $catName],
                        ['description' => 'Kategori dibuat otomatis dari import produk']
                    );
                    
                    $isReturnable = true;
                    if (isset($row[13])) {
                        $returStr = strtolower(trim($row[13]));
                        if ($returStr === 'tidak' || $returStr === 'false' || $returStr === '0') {
                            $isReturnable = false;
                        }
                    }

                    $product = \App\Models\Product::updateOrCreate(
                        ['sku' => trim($row[0])],
                        [
                            'name' => trim($row[1]),
                            'category_id' => $category->id,
                            'barcode' => isset($row[3]) && trim($row[3]) !== '' ? trim($row[3]) : null,
                            'brand' => isset($row[4]) && trim($row[4]) !== '' ? trim($row[4]) : null,
                            'unit' => isset($row[5]) && trim($row[5]) !== '' ? trim($row[5]) : 'Pcs',
                            'weight' => isset($row[6]) && is_numeric(trim($row[6])) ? floatval(trim($row[6])) : null,
                            'length' => isset($row[7]) && is_numeric(trim($row[7])) ? floatval(trim($row[7])) : null,
                            'width' => isset($row[8]) && is_numeric(trim($row[8])) ? floatval(trim($row[8])) : null,
                            'height' => isset($row[9]) && is_numeric(trim($row[9])) ? floatval(trim($row[9])) : null,
                            'stock_method' => isset($row[10]) && trim($row[10]) !== '' ? strtolower(trim($row[10])) : 'fifo',
                            'status' => isset($row[11]) && trim($row[11]) !== '' ? ucfirst(strtolower(trim($row[11]))) : 'Aktif',
                            'description' => isset($row[12]) && trim($row[12]) !== '' ? trim($row[12]) : null,
                            'is_returnable' => $isReturnable,
                            'ori_discount_percent' => isset($row[14]) && is_numeric(trim($row[14])) ? floatval(trim($row[14])) : 0,
                            'ori_cashback_percent' => isset($row[15]) && is_numeric(trim($row[15])) ? floatval(trim($row[15])) : 0,
                        ]
                    );

                    $count++;
                }
            }
            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            fclose($handle);
            return response()->json(['message' => 'Gagal mengimpor: ' . $e->getMessage()], 500);
        }
        
        fclose($handle);
        return response()->json(['message' => "$count Master Produk berhasil diimpor"]);
    }
}
