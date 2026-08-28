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
        Schema::table('goods_receipts', function (Blueprint $table) {
            if (!Schema::hasColumn('goods_receipts', 'sales_name')) {
                $table->string('sales_name')->nullable()->after('invoice_number_supplier');
            }
            if (!Schema::hasColumn('goods_receipts', 'received_date')) {
                $table->date('received_date')->nullable()->after('date');
            }
            if (!Schema::hasColumn('goods_receipts', 'due_date')) {
                $table->date('due_date')->nullable()->after('received_date');
            }
            if (!Schema::hasColumn('goods_receipts', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('notes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->dropColumn(['sales_name', 'received_date', 'due_date', 'rejection_reason']);
        });
    }
};
