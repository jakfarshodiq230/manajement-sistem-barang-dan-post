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
        Schema::create('payable_statements', function (Blueprint $table) {
            $table->id();
            $table->string('statement_number')->unique();
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('period_month', 7); // e.g. "2026-08"
            $table->integer('period_year')->default(2026);
            $table->integer('cutoff_day')->default(25);
            $table->date('period_start_date'); // e.g. "2026-07-26"
            $table->date('period_end_date');   // e.g. "2026-08-25"
            $table->date('due_date')->nullable(); // e.g. "2026-09-25"
            $table->integer('total_invoices_count')->default(0);
            $table->decimal('total_purchases_amount', 15, 2)->default(0);
            $table->decimal('total_returns_deduction', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2)->default(0);
            $table->enum('status', ['unpaid', 'partial', 'paid', 'overdue'])->default('unpaid');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['supplier_id', 'period_month', 'branch_id'], 'sup_period_branch_unique');
            $table->index(['supplier_id', 'status']);
            $table->index(['period_month', 'status']);
            $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payable_statements');
    }
};
