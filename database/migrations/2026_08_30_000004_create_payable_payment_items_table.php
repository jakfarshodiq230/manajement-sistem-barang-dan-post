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
        Schema::dropIfExists('payable_payment_items');

        Schema::create('payable_payment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payable_payment_id')->constrained('payable_payments')->onDelete('cascade');
            $table->foreignId('goods_receipt_item_id')->nullable()->constrained('goods_receipt_items')->nullOnDelete();
            $table->foreignId('payable_id')->nullable()->constrained('payables')->nullOnDelete();
            $table->decimal('allocated_amount', 15, 2)->default(0);
            $table->timestamps();

            $table->index(['payable_payment_id', 'goods_receipt_item_id'], 'idx_pay_item_alloc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payable_payment_items');
    }
};
