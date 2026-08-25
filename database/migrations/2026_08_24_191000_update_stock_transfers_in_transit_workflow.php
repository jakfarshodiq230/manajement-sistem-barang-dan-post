<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Alter status column on stock_transfers to include 'in_transit'
        DB::statement("ALTER TABLE stock_transfers MODIFY COLUMN status ENUM('pending', 'approved', 'ready_for_pickup', 'in_transit', 'completed', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending'");

        Schema::table('stock_transfers', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_transfers', 'picked_up_at')) {
                $table->timestamp('picked_up_at')->nullable()->after('picked_up_by_name');
            }
            if (!Schema::hasColumn('stock_transfers', 'pickup_courier_type')) {
                $table->string('pickup_courier_type')->nullable()->after('picked_up_at');
            }
            if (!Schema::hasColumn('stock_transfers', 'pickup_photo')) {
                $table->text('pickup_photo')->nullable()->after('pickup_courier_type');
            }
            if (!Schema::hasColumn('stock_transfers', 'received_photo')) {
                $table->text('received_photo')->nullable()->after('pickup_photo');
            }
            if (!Schema::hasColumn('stock_transfers', 'receive_notes')) {
                $table->text('receive_notes')->nullable()->after('received_photo');
            }
        });

        Schema::table('stock_transfer_items', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_transfer_items', 'qty_picked')) {
                $table->integer('qty_picked')->nullable()->after('qty_prepared');
            }
            if (!Schema::hasColumn('stock_transfer_items', 'qty_received')) {
                $table->integer('qty_received')->nullable()->after('qty_picked');
            }
            if (!Schema::hasColumn('stock_transfer_items', 'receive_condition')) {
                $table->enum('receive_condition', ['good', 'damaged', 'missing'])->default('good')->after('qty_received');
            }
            if (!Schema::hasColumn('stock_transfer_items', 'item_notes')) {
                $table->text('item_notes')->nullable()->after('cancel_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->dropColumn(['qty_picked', 'qty_received', 'receive_condition', 'item_notes']);
        });

        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropColumn(['picked_up_at', 'pickup_courier_type', 'pickup_photo', 'received_photo', 'receive_notes']);
        });

        DB::statement("ALTER TABLE stock_transfers MODIFY COLUMN status ENUM('pending', 'approved', 'ready_for_pickup', 'completed', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending'");
    }
};
