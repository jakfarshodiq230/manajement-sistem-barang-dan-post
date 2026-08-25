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
        Schema::create('branch_capitals', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no')->unique();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('cash_shift_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->enum('type', ['injection', 'return'])->comment('injection = Modal Masuk ke Cabang, return = Setoran Modal Balik ke Owner');
            $table->string('category')->default('Modal Awal')->comment('Modal Awal, Modal Tambahan Stok, Modal Aset, Setoran Laba Closing, Pelunasan');
            $table->decimal('amount', 15, 2);
            $table->date('date');
            $table->string('payment_method')->default('Transfer Bank')->comment('Transfer Bank, Tunai, Cek/Giro');
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_name')->nullable();
            $table->string('proof_file')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('approved');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('cash_shift_id')->references('id')->on('cash_shifts')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_capitals');
    }
};
