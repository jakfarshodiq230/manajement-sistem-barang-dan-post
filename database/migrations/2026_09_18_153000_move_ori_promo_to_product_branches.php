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
            if (!Schema::hasColumn('product_branches', 'ori_discount_percent')) {
                $table->decimal('ori_discount_percent', 5, 2)->default(0)->comment('Discount percentage for Promo ORI in this branch');
            }
            if (!Schema::hasColumn('product_branches', 'ori_cashback_percent')) {
                $table->decimal('ori_cashback_percent', 5, 2)->default(0)->comment('Cashback percentage for Promo ORI in this branch');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'ori_discount_percent')) {
                $table->dropColumn('ori_discount_percent');
            }
            if (Schema::hasColumn('products', 'ori_cashback_percent')) {
                $table->dropColumn('ori_cashback_percent');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('ori_discount_percent', 5, 2)->default(0);
            $table->decimal('ori_cashback_percent', 5, 2)->default(0);
        });

        Schema::table('product_branches', function (Blueprint $table) {
            $table->dropColumn(['ori_discount_percent', 'ori_cashback_percent']);
        });
    }
};
