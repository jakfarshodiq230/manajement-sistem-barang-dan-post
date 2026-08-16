<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductBranch;
use App\Models\Branch;
use App\Models\Category;

class TestingProductBranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Ensure there is at least one branch and category
        $branch = Branch::first();
        if (!$branch) {
            $this->command->error('Tidak ada data Cabang. Harap buat cabang terlebih dahulu.');
            return;
        }

        $category = Category::first();
        if (!$category) {
            $category = Category::create([
                'name' => 'Kategori Testing',
                'description' => 'Kategori untuk testing stok'
            ]);
        }

        $this->command->info('Membuat 10 data barang dummy dengan stok <= 3 dan harga nego...');

        for ($i = 1; $i <= 10; $i++) {
            $product = Product::create([
                'category_id' => $category->id,
                'name' => 'Barang Dummy Test ' . $i,
                'sku' => 'TEST-00' . $i,
                'description' => 'Data untuk testing batas stok dan harga nego'
            ]);

            // Random stock between 1 and 3
            $stock = rand(1, 3);
            
            // Random price and nego limits
            $costPrice = rand(10, 50) * 1000;
            $sellingPrice = $costPrice + rand(10, 30) * 1000;
            // Nego price is between cost price and selling price
            $negoPrice = $costPrice + rand(2, 8) * 1000;

            ProductBranch::create([
                'product_id' => $product->id,
                'branch_id' => $branch->id,
                'cost_price' => $costPrice,
                'price' => $sellingPrice,
                'min_nego_price' => $negoPrice,
                'tax_percentage' => 0,
                'other_fees' => 0,
                'stock' => $stock
            ]);
        }

        $this->command->info('10 data barang testing berhasil ditambahkan!');
    }
}
