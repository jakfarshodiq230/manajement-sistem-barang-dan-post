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
        // 1. Chart of Accounts (COA)
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'cogs', 'expense']);
            $table->string('category', 60)->default('general'); // e.g., current_asset, fixed_asset, current_liability, operating_expense, etc.
            $table->enum('normal_balance', ['debit', 'credit'])->default('debit');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable(); // null for global COA or specific branch
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->boolean('is_system')->default(false); // Locked system accounts (e.g. Retained Earnings)
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('accounts')->onDelete('set null');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->index(['type', 'is_active']);
            $table->index(['code', 'name']);
        });

        // 2. Default Account Settings Mapping
        Schema::create('account_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('setting_key', 60); // e.g. default_cash, default_sales, default_cogs, default_inventory, default_ar, default_ap, default_retained_earnings
            $table->unsignedBigInteger('account_id');
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->unique(['branch_id', 'setting_key']);
        });

        // 3. Journal Entries Header
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_number', 50)->unique();
            $table->date('entry_date');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('reference_type', 60)->nullable(); // e.g. Sale, GoodsReceipt, PayablePayment, ReceivablePayment, PettyCash, BranchCapital, Manual
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['posted', 'draft', 'void'])->default('posted');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['entry_date', 'branch_id']);
            $table->index(['reference_type', 'reference_id']);
        });

        // 4. Journal Entry Items (Debit / Credit Rows)
        Schema::create('journal_entry_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('journal_entry_id');
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->string('memo', 255)->nullable();
            $table->timestamps();

            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->onDelete('cascade');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('restrict');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->index(['account_id', 'journal_entry_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entry_items');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('account_settings');
        Schema::dropIfExists('accounts');
    }
};
