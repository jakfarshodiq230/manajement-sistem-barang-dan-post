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
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->string('recipient_email');
            $table->string('recipient_name')->nullable();
            $table->string('subject');
            $table->string('email_type', 50); // receivable_invoice, receivable_receipt, receivable_reminder, capital_installment, capital_summary
            $table->enum('trigger_mode', ['automatic', 'manual'])->default('manual');
            $table->string('reference_type', 100);
            $table->string('reference_id', 50);
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->dateTime('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['reference_type', 'reference_id'], 'idx_email_ref');
            $table->index('status', 'idx_email_status');
            $table->index('branch_id', 'idx_email_branch');
            $table->index('created_at', 'idx_email_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
