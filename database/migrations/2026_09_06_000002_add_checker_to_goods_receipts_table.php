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
            if (!Schema::hasColumn('goods_receipts', 'checker_name')) {
                $table->string('checker_name')->nullable()->after('sales_name');
            }
            if (!Schema::hasColumn('goods_receipts', 'checker_employee_id')) {
                $table->foreignId('checker_employee_id')->nullable()->after('checker_name')->constrained('employees')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table) {
            if (Schema::hasColumn('goods_receipts', 'checker_employee_id')) {
                $table->dropForeign(['checker_employee_id']);
                $table->dropColumn('checker_employee_id');
            }
            if (Schema::hasColumn('goods_receipts', 'checker_name')) {
                $table->dropColumn('checker_name');
            }
        });
    }
};
