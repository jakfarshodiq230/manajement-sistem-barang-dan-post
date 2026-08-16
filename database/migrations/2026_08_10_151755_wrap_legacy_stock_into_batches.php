<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Temukan semua product branch yang memiliki stok > 0
        $branches = \Illuminate\Support\Facades\DB::table('product_branches')->where('stock', '>', 0)->get();

        foreach ($branches as $branch) {
            // Cek apakah sudah ada batch untuk produk ini (menghindari duplikasi saat migrasi ulang)
            $exists = \Illuminate\Support\Facades\DB::table('product_batches')
                ->where('product_branch_id', $branch->id)
                ->exists();

            if (!$exists) {
                // Bungkus stok lama sebagai 1 batch awal
                \Illuminate\Support\Facades\DB::table('product_batches')->insert([
                    'product_branch_id' => $branch->id,
                    'qty' => $branch->stock,
                    'cost_price' => $branch->cost_price,
                    'entry_date' => now(), // Dianggap masuk hari ini
                    'expiration_date' => null, // Legacy stock tidak punya tanggal kedaluwarsa
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tidak perlu rollback karena tabel product_batches akan dihapus seluruhnya oleh down() migrasi create_product_batches_table
    }
};
