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
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('bank_name'); // BCA, Bank Mandiri, BRI, BNI, BSI, QRIS, Kasir, etc.
            $table->string('account_number')->nullable(); // No Rekening / Merchant ID
            $table->string('account_name')->nullable(); // Atas Nama Pemilik
            $table->enum('type', ['bank_transfer', 'qris', 'edc_debit', 'edc_credit', 'cash'])->default('bank_transfer');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete(); // Null = Global / Semua Cabang
            $table->decimal('initial_balance', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->string('qris_image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->string('color', 20)->nullable(); // Hex color for card styling
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['branch_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
