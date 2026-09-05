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
        // 1. Master Penyesuaian Harga Periode
        Schema::create('price_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('adjustment_number', 50)->unique();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('title');
            $table->date('effective_date');
            $table->string('reason')->nullable();
            $table->enum('status', ['draft', 'approved', 'cancelled'])->default('draft');
            $table->integer('total_items')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['effective_date', 'status']);
        });

        // 2. Item Rincian Penyesuaian Harga
        Schema::create('price_adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_adjustment_id')->constrained('price_adjustments')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_branch_id')->nullable()->constrained('product_branches')->nullOnDelete();
            $table->decimal('old_cost_price', 15, 2)->default(0);
            $table->decimal('new_cost_price', 15, 2)->default(0);
            $table->decimal('old_price', 15, 2)->default(0);
            $table->decimal('new_price', 15, 2)->default(0);
            $table->decimal('old_min_nego_price', 15, 2)->default(0);
            $table->decimal('new_min_nego_price', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 3. Log Riwayat Perubahan Harga (Audit Trail)
        Schema::create('price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_branch_id')->nullable()->constrained('product_branches')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('price_adjustment_id')->nullable()->constrained('price_adjustments')->nullOnDelete();
            $table->string('adjustment_number', 50)->nullable();
            $table->decimal('old_cost_price', 15, 2)->default(0);
            $table->decimal('new_cost_price', 15, 2)->default(0);
            $table->decimal('old_price', 15, 2)->default(0);
            $table->decimal('new_price', 15, 2)->default(0);
            $table->decimal('old_min_nego_price', 15, 2)->default(0);
            $table->decimal('new_min_nego_price', 15, 2)->default(0);
            $table->date('effective_date');
            $table->string('reason')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['product_id', 'effective_date']);
            $table->index(['branch_id', 'effective_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_histories');
        Schema::dropIfExists('price_adjustment_items');
        Schema::dropIfExists('price_adjustments');
    }
};
