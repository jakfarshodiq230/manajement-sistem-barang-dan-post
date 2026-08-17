<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 2. Transaksi & Inventori
        Schema::table('sales', function (Blueprint $table) {
            $table->index('status');
            $table->index('created_at');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->index('status');
            $table->index('created_at');
        });

        Schema::table('product_branches', function (Blueprint $table) {
            $table->index('stock');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index('type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('product_branches', function (Blueprint $table) {
            $table->dropIndex(['stock']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropIndex(['created_at']);
        });
    }
};
