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
        Schema::create('return_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('return_number')->unique();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->string('reference_type'); // 'purchase' or 'sale'
            $table->unsignedBigInteger('reference_id');
            
            $table->enum('return_type', ['tukar_barang', 'pengembalian_uang']);
            $table->enum('status', ['pending', 'approved', 'completed', 'rejected'])->default('pending');
            
            $table->decimal('total_amount', 15, 2)->default(0); // for refund amount
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Index for polymorphic-like references
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_transactions');
    }
};
