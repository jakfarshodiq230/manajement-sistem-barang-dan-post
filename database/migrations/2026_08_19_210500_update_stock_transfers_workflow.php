<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Alter status column on stock_transfers to support ready_for_pickup
        // Use DB statement for MySQL enum alter
        DB::statement("ALTER TABLE stock_transfers MODIFY COLUMN status ENUM('pending', 'approved', 'ready_for_pickup', 'completed', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending'");

        Schema::table('stock_transfers', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_transfers', 'prepared_by')) {
                $table->foreignId('prepared_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('stock_transfers', 'prepared_at')) {
                $table->timestamp('prepared_at')->nullable()->after('prepared_by');
            }
            if (!Schema::hasColumn('stock_transfers', 'received_by')) {
                $table->foreignId('received_by')->nullable()->after('approved_by')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('stock_transfers', 'received_at')) {
                $table->timestamp('received_at')->nullable()->after('received_by');
            }
        });

        Schema::table('stock_transfer_items', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_transfer_items', 'batches_data')) {
                $table->json('batches_data')->nullable()->after('qty');
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
            if (Schema::hasColumn('stock_transfer_items', 'batches_data')) {
                $table->dropColumn('batches_data');
            }
        });

        Schema::table('stock_transfers', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('stock_transfers', 'prepared_by')) $columns[] = 'prepared_by';
            if (Schema::hasColumn('stock_transfers', 'prepared_at')) $columns[] = 'prepared_at';
            if (Schema::hasColumn('stock_transfers', 'received_by')) $columns[] = 'received_by';
            if (Schema::hasColumn('stock_transfers', 'received_at')) $columns[] = 'received_at';
            
            if (!empty($columns)) {
                $table->dropForeign(['prepared_by']);
                $table->dropForeign(['received_by']);
                $table->dropColumn($columns);
            }
        });

        DB::statement("ALTER TABLE stock_transfers MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");
    }
};
