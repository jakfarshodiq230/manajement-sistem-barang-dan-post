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
        if (Schema::hasTable('goods_receipt_items')) {
            Schema::table('goods_receipt_items', function (Blueprint $table) {
                if (!Schema::hasColumn('goods_receipt_items', 'batch_number')) {
                    $table->string('batch_number')->nullable()->after('expiration_date');
                }
                if (!Schema::hasColumn('goods_receipt_items', 'scc_code')) {
                    $table->string('scc_code')->nullable()->after('batch_number');
                }
                if (!Schema::hasColumn('goods_receipt_items', 'is_received')) {
                    $table->boolean('is_received')->default(true)->after('scc_code');
                }
                if (!Schema::hasColumn('goods_receipt_items', 'qty_rejected')) {
                    $table->integer('qty_rejected')->default(0)->after('is_received');
                }
                if (!Schema::hasColumn('goods_receipt_items', 'rejection_reason')) {
                    $table->string('rejection_reason')->nullable()->after('qty_rejected');
                }
            });
        }

        if (Schema::hasTable('product_batches')) {
            Schema::table('product_batches', function (Blueprint $table) {
                if (!Schema::hasColumn('product_batches', 'batch_number')) {
                    $table->string('batch_number')->nullable()->after('product_branch_id');
                }
                if (!Schema::hasColumn('product_batches', 'scc_code')) {
                    $table->string('scc_code')->nullable()->after('batch_number');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('goods_receipt_items')) {
            Schema::table('goods_receipt_items', function (Blueprint $table) {
                $cols = ['batch_number', 'scc_code', 'is_received', 'qty_rejected', 'rejection_reason'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('goods_receipt_items', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('product_batches')) {
            Schema::table('product_batches', function (Blueprint $table) {
                $cols = ['batch_number', 'scc_code'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('product_batches', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
