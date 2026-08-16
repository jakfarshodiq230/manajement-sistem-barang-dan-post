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
            $table->decimal('original_price', 15, 2)->after('qty')->default(0);
            $table->decimal('tax_percentage', 5, 2)->after('price')->default(0);
            $table->decimal('tax_amount', 15, 2)->after('tax_percentage')->default(0);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('total_tax', 15, 2)->after('discount')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['original_price', 'tax_percentage', 'tax_amount']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('total_tax');
        });
    }
};
