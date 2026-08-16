<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Branch;
use App\Models\ProductBranch;
use App\Models\ProductBatch;
use App\Models\Category;

class DummyBatchSeeder extends Seeder
{
    public function run()
    {
        // Get first branch and category
        $branch = Branch::first();
        $category = Category::first();

        if (!$branch || !$category) {
            echo "Branch atau Category tidak ditemukan. Pastikan data master sudah ada.\n";
            return;
        }

        // 1. Create a Product
        $product = Product::firstOrCreate(
            ['sku' => 'BML-2L-001'],
            [
                'name' => 'Minyak Goreng Bimoli 2L (Demo Batch)',
                'category_id' => $category->id,
                'description' => 'Produk contoh untuk mendemonstrasikan fitur multi-batch dan multi-harga.',
                'stock_method' => 'fifo',
            ]
        );

        // 2. Create Product Branch
        $productBranch = ProductBranch::firstOrCreate(
            ['product_id' => $product->id, 'branch_id' => $branch->id],
            [
                'cost_price' => 30000,
                'price' => 35000,
                'min_nego_price' => 33000,
                'stock' => 25, // Total dari 2 batch
                'tax_percentage' => 0,
            ]
        );
        
        // Perbarui stok jika sebelumnya sudah ada
        $productBranch->update(['stock' => 25]);

        // Hapus batch lama jika ada (untuk demo)
        ProductBatch::where('product_branch_id', $productBranch->id)->delete();

        // 3. Create Batches
        // Batch 1 (Harga Lama)
        ProductBatch::create([
            'product_branch_id' => $productBranch->id,
            'qty' => 10,
            'cost_price' => 30000,
            'price' => 35000,
            'min_nego_price' => 33000,
            'entry_date' => now()->subDays(10)->toDateString(),
            'expiration_date' => now()->addMonths(6)->toDateString(),
        ]);

        // Batch 2 (Harga Baru - Naik)
        ProductBatch::create([
            'product_branch_id' => $productBranch->id,
            'qty' => 15,
            'cost_price' => 32000,
            'price' => 38000,
            'min_nego_price' => 36000,
            'entry_date' => now()->toDateString(),
            'expiration_date' => now()->addMonths(8)->toDateString(),
        ]);

        echo "Berhasil membuat data contoh 'Minyak Goreng Bimoli 2L' dengan 2 batch berbeda!\n";
    }
}
