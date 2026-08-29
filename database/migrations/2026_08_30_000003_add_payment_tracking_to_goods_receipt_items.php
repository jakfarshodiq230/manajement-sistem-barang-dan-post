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
        Schema::table('goods_receipt_items', function (Blueprint $table) {
            if (!Schema::hasColumn('goods_receipt_items', 'paid_amount')) {
                $table->decimal('paid_amount', 15, 2)->default(0)->after('price');
            }
            if (!Schema::hasColumn('goods_receipt_items', 'remaining_amount')) {
                $table->decimal('remaining_amount', 15, 2)->nullable()->after('paid_amount');
            }
            if (!Schema::hasColumn('goods_receipt_items', 'payment_status')) {
                $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid')->after('remaining_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('goods_receipt_items', function (Blueprint $table) {
            if (Schema::hasColumn('goods_receipt_items', 'payment_status')) {
                $table->dropColumn('payment_status');
            }
            if (Schema::hasColumn('goods_receipt_items', 'remaining_amount')) {
                $table->dropColumn('remaining_amount');
            }
            if (Schema::hasColumn('goods_receipt_items', 'paid_amount')) {
                $table->dropColumn('paid_amount');
            }
        });
    }
};
