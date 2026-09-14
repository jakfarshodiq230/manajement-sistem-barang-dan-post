<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_transfers', 'is_capital_transfer')) {
                $table->boolean('is_capital_transfer')
                    ->default(false)
                    ->after('notes')
                    ->comment('True jika mutasi ini adalah distribusi modal barang dari Pusat ke Cabang');
            }
            if (!Schema::hasColumn('stock_transfers', 'capital_value')) {
                $table->decimal('capital_value', 15, 2)
                    ->nullable()
                    ->after('is_capital_transfer')
                    ->comment('Total nilai HPP (harga pokok) modal barang yang dikirim');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            if (Schema::hasColumn('stock_transfers', 'capital_value')) {
                $table->dropColumn('capital_value');
            }
            if (Schema::hasColumn('stock_transfers', 'is_capital_transfer')) {
                $table->dropColumn('is_capital_transfer');
            }
        });
    }
};
