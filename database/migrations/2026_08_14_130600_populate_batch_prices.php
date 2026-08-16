<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Populate existing batches with branch's prices
        $batches = DB::table('product_batches')
            ->join('product_branches', 'product_batches.product_branch_id', '=', 'product_branches.id')
            ->select('product_batches.id', 'product_branches.price', 'product_branches.min_nego_price')
            ->get();
            
        foreach ($batches as $batch) {
            DB::table('product_batches')->where('id', $batch->id)->update([
                'price' => $batch->price,
                'min_nego_price' => $batch->min_nego_price
            ]);
        }
    }

    public function down(): void
    {
        // No down migration needed
    }
};
