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
        Schema::table('stock_transfer_items', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_transfer_items', 'qty_prepared')) {
                $table->integer('qty_prepared')->nullable()->after('qty');
            }
            if (!Schema::hasColumn('stock_transfer_items', 'status')) {
                $table->string('status')->default('pending')->after('qty_prepared'); // pending, prepared, cancelled
            }
            if (!Schema::hasColumn('stock_transfer_items', 'cancel_reason')) {
                $table->string('cancel_reason')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('stock_transfer_items', 'qty_prepared')) $columns[] = 'qty_prepared';
            if (Schema::hasColumn('stock_transfer_items', 'status')) $columns[] = 'status';
            if (Schema::hasColumn('stock_transfer_items', 'cancel_reason')) $columns[] = 'cancel_reason';
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
