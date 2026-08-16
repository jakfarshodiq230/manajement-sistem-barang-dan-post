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
        Schema::table('product_branches', function (Blueprint $table) {
            $table->decimal('cost_price', 15, 2)->default(0)->after('branch_id');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->decimal('unit_cost', 15, 2)->default(0)->after('quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropColumn('unit_cost');
        });

        Schema::table('product_branches', function (Blueprint $table) {
            $table->dropColumn('cost_price');
        });
    }
};
