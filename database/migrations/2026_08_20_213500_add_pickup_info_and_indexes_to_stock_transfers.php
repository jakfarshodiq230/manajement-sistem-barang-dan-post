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
        Schema::table('stock_transfers', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_transfers', 'picked_up_by_name')) {
                $table->string('picked_up_by_name')->nullable()->after('received_by');
            }
            if (!Schema::hasColumn('stock_transfers', 'pickup_notes')) {
                $table->string('pickup_notes')->nullable()->after('picked_up_by_name');
            }
            
            // Indexes for fast querying & filtering
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            if (Schema::hasColumn('stock_transfers', 'picked_up_by_name')) {
                $table->dropColumn('picked_up_by_name');
            }
            if (Schema::hasColumn('stock_transfers', 'pickup_notes')) {
                $table->dropColumn('pickup_notes');
            }
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
        });
    }
};
