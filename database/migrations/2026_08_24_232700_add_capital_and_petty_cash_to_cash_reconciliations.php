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
        Schema::table('cash_reconciliations', function (Blueprint $table) {
            if (!Schema::hasColumn('cash_reconciliations', 'capital_returns_amount')) {
                $table->decimal('capital_returns_amount', 15, 2)->default(0)->after('receivable_payments_amount');
            }
            if (!Schema::hasColumn('cash_reconciliations', 'capital_injections_amount')) {
                $table->decimal('capital_injections_amount', 15, 2)->default(0)->after('capital_returns_amount');
            }
            if (!Schema::hasColumn('cash_reconciliations', 'petty_cash_amount')) {
                $table->decimal('petty_cash_amount', 15, 2)->default(0)->after('capital_injections_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_reconciliations', function (Blueprint $table) {
            $table->dropColumn(['capital_returns_amount', 'capital_injections_amount', 'petty_cash_amount']);
        });
    }
};
