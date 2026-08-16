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
        Schema::table('products', function (Blueprint $table) {
            $table->string('brand')->nullable()->after('category_id');
            $table->string('barcode')->nullable()->after('sku');
            $table->string('unit')->default('Pcs')->after('barcode');
            $table->decimal('weight', 10, 2)->nullable()->comment('Dalam Gram')->after('unit');
            $table->decimal('length', 10, 2)->nullable()->after('weight');
            $table->decimal('width', 10, 2)->nullable()->after('length');
            $table->decimal('height', 10, 2)->nullable()->after('width');
            $table->boolean('is_returnable')->default(true)->after('height');
            $table->string('tax_type')->nullable()->after('is_returnable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'brand', 'barcode', 'unit', 'weight', 
                'length', 'width', 'height', 
                'is_returnable', 'tax_type'
            ]);
        });
    }
};
