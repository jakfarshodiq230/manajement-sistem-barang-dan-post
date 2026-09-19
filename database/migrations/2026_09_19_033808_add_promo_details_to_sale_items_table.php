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
        Schema::table('sale_items', function (Blueprint $table) {
            $table->string('ori_promo_name', 100)->nullable()->after('ori_promo_type');
            $table->string('ori_promo_discount_type', 20)->nullable()->after('ori_promo_name');
            $table->decimal('ori_promo_discount_value', 12, 2)->nullable()->after('ori_promo_discount_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn([
                'ori_promo_name',
                'ori_promo_discount_type',
                'ori_promo_discount_value',
            ]);
        });
    }
};
