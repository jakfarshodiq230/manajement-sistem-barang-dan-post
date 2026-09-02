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
        Schema::table('petty_cashes', function (Blueprint $table) {
            if (!Schema::hasColumn('petty_cashes', 'payment_method')) {
                $table->string('payment_method')->default('cash')->after('category');
            }
            if (!Schema::hasColumn('petty_cashes', 'bank_account_id')) {
                $table->foreignId('bank_account_id')->nullable()->after('payment_method')->constrained('bank_accounts')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('petty_cashes', function (Blueprint $table) {
            if (Schema::hasColumn('petty_cashes', 'bank_account_id')) {
                $table->dropForeign(['bank_account_id']);
                $table->dropColumn('bank_account_id');
            }
            if (Schema::hasColumn('petty_cashes', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
        });
    }
};
