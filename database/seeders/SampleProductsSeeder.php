<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SampleProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoryId = \App\Models\Category::firstOrCreate(['name' => 'Kategori Contoh'])->id;
        $branchId = \App\Models\Branch::first()->id ?? 1; // Fallback if no branch

        // 1. Elektronik (Exclude PPN, Bisa Diretur)
        $p1 = \App\Models\Product::updateOrCreate(['sku' => 'SKU-TV-001'], [
            'name' => 'Smart TV 32 Inch',
            'category_id' => $categoryId,
            'brand' => 'Samsung',
            'barcode' => '123456789011',
            'unit' => 'Unit',
            'weight' => 5000,
            'is_returnable' => true,
            'tax_type' => 'Exclude PPN',
            'stock_method' => 'fifo',
            'status' => 'Aktif'
        ]);

        \App\Models\ProductBranch::updateOrCreate([
            'branch_id' => $branchId,
            'product_id' => $p1->id
        ], [
            'price' => 2000000,
            'min_nego_price' => 1950000,
            'cost_price' => 1500000,
            'stock' => 10,
            'tax_percentage' => 11
        ]);

        // 2. Sembako (Include PPN, Bisa Diretur)
        $p2 = \App\Models\Product::updateOrCreate(['sku' => 'SKU-S-002'], [
            'name' => 'Beras Pandan Wangi 5kg',
            'category_id' => $categoryId,
            'brand' => 'Bimoli', // Wait, Beras not Bimoli, but ok just sample
            'barcode' => '8991234567890',
            'unit' => 'Sak',
            'weight' => 5000,
            'is_returnable' => true,
            'tax_type' => 'Include PPN',
            'stock_method' => 'fifo',
            'status' => 'Aktif'
        ]);

        \App\Models\ProductBranch::updateOrCreate([
            'branch_id' => $branchId,
            'product_id' => $p2->id
        ], [
            'price' => 85000, // Harga ini sudah termasuk PPN di dalamnya
            'min_nego_price' => 85000,
            'cost_price' => 70000,
            'stock' => 50,
            'tax_percentage' => 11
        ]);

        // 3. Makanan Fresh / Diskon Cuci Gudang (Non-Tax, TIDAK BISA DIRETUR)
        $p3 = \App\Models\Product::updateOrCreate(['sku' => 'SKU-F-003'], [
            'name' => 'Sayur Organik Segar (Cuci Gudang)',
            'category_id' => $categoryId,
            'brand' => 'Lokal',
            'barcode' => '000111222333',
            'unit' => 'Pack',
            'weight' => 500,
            'is_returnable' => false, // Tidak bisa retur!
            'tax_type' => 'Non-Tax',
            'stock_method' => 'fefo',
            'status' => 'Aktif'
        ]);

        \App\Models\ProductBranch::updateOrCreate([
            'branch_id' => $branchId,
            'product_id' => $p3->id
        ], [
            'price' => 20000,
            'min_nego_price' => 15000,
            'cost_price' => 10000,
            'stock' => 30,
            'tax_percentage' => 0
        ]);
    }
}
