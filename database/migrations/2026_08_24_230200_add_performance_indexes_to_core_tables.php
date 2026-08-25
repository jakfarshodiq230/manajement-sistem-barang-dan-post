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
        // 1. Sales table (Speed up date-range analytics & payment filter)
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'date')) {
                $table->index('date', 'sales_date_index');
            }
            if (Schema::hasColumn('sales', 'payment_method')) {
                $table->index('payment_method', 'sales_payment_method_index');
            }
        });

        // 2. Branch Capitals (Speed up summary & status filters)
        Schema::table('branch_capitals', function (Blueprint $table) {
            $table->index('type', 'branch_capitals_type_index');
            $table->index('status', 'branch_capitals_status_index');
            $table->index('date', 'branch_capitals_date_index');
            $table->index(['branch_id', 'type', 'status'], 'branch_capitals_composite_calc_index');
        });

        // 3. Product Batches (Speed up FEFO & Expiration queries)
        Schema::table('product_batches', function (Blueprint $table) {
            if (Schema::hasColumn('product_batches', 'expiration_date')) {
                $table->index('expiration_date', 'product_batches_exp_date_index');
            }
            if (Schema::hasColumn('product_batches', 'qty')) {
                $table->index('qty', 'product_batches_qty_index');
            }
        });

        // 4. Receivables & Payments (Speed up overdue debt & collection reports)
        Schema::table('receivables', function (Blueprint $table) {
            if (Schema::hasColumn('receivables', 'status')) {
                $table->index('status', 'receivables_status_index');
            }
            if (Schema::hasColumn('receivables', 'due_date')) {
                $table->index('due_date', 'receivables_due_date_index');
            }
        });

        Schema::table('receivable_payments', function (Blueprint $table) {
            if (Schema::hasColumn('receivable_payments', 'payment_date')) {
                $table->index('payment_date', 'receivable_payments_date_index');
            }
        });

        // 5. Cash Shifts (Speed up shift reports & reconciliation)
        Schema::table('cash_shifts', function (Blueprint $table) {
            if (Schema::hasColumn('cash_shifts', 'opened_at')) {
                $table->index('opened_at', 'cash_shifts_opened_at_index');
            }
            if (Schema::hasColumn('cash_shifts', 'closed_at')) {
                $table->index('closed_at', 'cash_shifts_closed_at_index');
            }
        });

        // 6. Stock Movements (Speed up polymorphic audit trace)
        Schema::table('stock_movements', function (Blueprint $table) {
            if (Schema::hasColumn('stock_movements', 'reference_type') && Schema::hasColumn('stock_movements', 'reference_id')) {
                $table->index(['reference_type', 'reference_id'], 'stock_movements_reference_index');
            }
        });

        // 7. Products (Speed up POS Barcode scanner lookups)
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'barcode')) {
                $table->index('barcode', 'products_barcode_index');
            }
        });

        // 8. Stock Opnames
        Schema::table('stock_opnames', function (Blueprint $table) {
            if (Schema::hasColumn('stock_opnames', 'status')) {
                $table->index('status', 'stock_opnames_status_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_date_index');
            $table->dropIndex('sales_payment_method_index');
        });

        Schema::table('branch_capitals', function (Blueprint $table) {
            $table->dropIndex('branch_capitals_type_index');
            $table->dropIndex('branch_capitals_status_index');
            $table->dropIndex('branch_capitals_date_index');
            $table->dropIndex('branch_capitals_composite_calc_index');
        });

        Schema::table('product_batches', function (Blueprint $table) {
            $table->dropIndex('product_batches_exp_date_index');
            $table->dropIndex('product_batches_qty_index');
        });

        Schema::table('receivables', function (Blueprint $table) {
            $table->dropIndex('receivables_status_index');
            $table->dropIndex('receivables_due_date_index');
        });

        Schema::table('receivable_payments', function (Blueprint $table) {
            $table->dropIndex('receivable_payments_date_index');
        });

        Schema::table('cash_shifts', function (Blueprint $table) {
            $table->dropIndex('cash_shifts_opened_at_index');
            $table->dropIndex('cash_shifts_closed_at_index');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('stock_movements_reference_index');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_barcode_index');
        });

        Schema::table('stock_opnames', function (Blueprint $table) {
            $table->dropIndex('stock_opnames_status_index');
        });
    }
};
