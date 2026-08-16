<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stock_opname_items', function (Blueprint $table) {
            $table->integer('damaged_qty')->nullable()->after('physical_qty');
            $table->integer('sold_qty')->default(0)->after('damaged_qty');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stock_opname_items', function (Blueprint $table) {
            $table->dropColumn(['damaged_qty', 'sold_qty']);
        });
    }
};
