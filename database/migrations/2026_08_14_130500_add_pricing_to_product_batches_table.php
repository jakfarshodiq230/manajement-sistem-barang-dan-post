<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_batches', function (Blueprint $table) {
            $table->decimal('price', 15, 2)->default(0)->after('cost_price');
            $table->decimal('min_nego_price', 15, 2)->default(0)->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('product_batches', function (Blueprint $table) {
            $table->dropColumn(['price', 'min_nego_price']);
        });
    }
};
