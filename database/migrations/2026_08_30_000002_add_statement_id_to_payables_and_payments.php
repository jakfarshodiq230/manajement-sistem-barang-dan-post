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
        Schema::table('payables', function (Blueprint $table) {
            $table->foreignId('payable_statement_id')->nullable()->after('goods_receipt_id')->constrained('payable_statements')->nullOnDelete();
        });

        Schema::table('payable_payments', function (Blueprint $table) {
            $table->foreignId('payable_statement_id')->nullable()->after('payable_id')->constrained('payable_statements')->cascadeOnDelete();
            $table->foreignId('payable_id')->nullable()->change();
        });

        Schema::table('suppliers', function (Blueprint $table) {
            if (!Schema::hasColumn('suppliers', 'cutoff_day')) {
                $table->integer('cutoff_day')->default(25)->after('address');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers', 'cutoff_day')) {
                $table->dropColumn('cutoff_day');
            }
        });

        Schema::table('payable_payments', function (Blueprint $table) {
            $table->dropForeign(['payable_statement_id']);
            $table->dropColumn('payable_statement_id');
        });

        Schema::table('payables', function (Blueprint $table) {
            $table->dropForeign(['payable_statement_id']);
            $table->dropColumn('payable_statement_id');
        });
    }
};
