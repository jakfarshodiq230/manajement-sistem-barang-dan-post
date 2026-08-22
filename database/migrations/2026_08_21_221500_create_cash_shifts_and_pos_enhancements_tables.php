<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Cash Shifts (Shift Kasir)
        if (!Schema::hasTable('cash_shifts')) {
            Schema::create('cash_shifts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->decimal('start_cash', 15, 2)->default(0); // Kas awal / modal kembalian
                $table->decimal('total_cash_sales', 15, 2)->default(0); // Penjualan tunai
                $table->decimal('total_non_cash_sales', 15, 2)->default(0); // Transfer/QRIS
                $table->decimal('total_expenses', 15, 2)->default(0); // Pengeluaran kas kecil shift ini
                $table->decimal('expected_cash', 15, 2)->default(0); // start_cash + cash_sales - expenses
                $table->decimal('actual_cash', 15, 2)->nullable(); // Uang fisik di laci saat tutup shift
                $table->decimal('difference', 15, 2)->nullable(); // actual_cash - expected_cash
                $table->enum('status', ['open', 'closed'])->default('open')->index();
                $table->timestamp('opened_at')->useCurrent();
                $table->timestamp('closed_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        // 2. Petty Cashes (Kas Kecil / Operasional Cabang)
        if (!Schema::hasTable('petty_cashes')) {
            Schema::create('petty_cashes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('cash_shift_id')->nullable()->index();
                $table->string('category')->index(); // Operasional, Listrik, Air/Galon, Bensin/Kurir, ATK, Konsumsi, Lainnya
                $table->decimal('amount', 15, 2);
                $table->text('description');
                $table->string('receipt_image')->nullable();
                $table->date('date')->index();
                $table->timestamps();

                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        // 3. Pos Held Bills (Simpan Transaksi Sementara / Hold Bill)
        if (!Schema::hasTable('pos_held_bills')) {
            Schema::create('pos_held_bills', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->string('customer_name')->nullable();
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('discount', 15, 2)->default(0);
                $table->decimal('total', 15, 2)->default(0);
                $table->longText('items_json'); // JSON array of cart items
                $table->string('notes')->nullable();
                $table->timestamps();

                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        // 4. Customer Points & Point Histories
        if (!Schema::hasColumn('customers', 'points')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->integer('points')->default(0);
            });
        }

        if (!Schema::hasTable('customer_point_histories')) {
            Schema::create('customer_point_histories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_id')->index();
                $table->unsignedBigInteger('sale_id')->nullable()->index();
                $table->enum('type', ['earned', 'redeemed', 'adjusted'])->default('earned');
                $table->integer('points');
                $table->string('description')->nullable();
                $table->timestamps();

                $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customer_point_histories');
        if (Schema::hasColumn('customers', 'points')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('points');
            });
        }
        Schema::dropIfExists('pos_held_bills');
        Schema::dropIfExists('petty_cashes');
        Schema::dropIfExists('cash_shifts');
    }
};
