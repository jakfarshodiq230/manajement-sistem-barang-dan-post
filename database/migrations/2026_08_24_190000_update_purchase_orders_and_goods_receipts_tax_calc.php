<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_orders', 'invoice_number_supplier')) {
                $table->string('invoice_number_supplier')->nullable()->after('po_number');
            }
            if (!Schema::hasColumn('purchase_orders', 'due_date')) {
                $table->date('due_date')->nullable()->after('date');
            }
            if (!Schema::hasColumn('purchase_orders', 'tax_type')) {
                $table->enum('tax_type', ['include', 'exclude', 'none'])->default('include')->after('total_amount');
            }
            if (!Schema::hasColumn('purchase_orders', 'tax_percentage')) {
                $table->decimal('tax_percentage', 5, 2)->default(11.00)->after('tax_type');
            }
            if (!Schema::hasColumn('purchase_orders', 'dpp_amount')) {
                $table->decimal('dpp_amount', 15, 2)->default(0)->after('tax_percentage');
            }
            if (!Schema::hasColumn('purchase_orders', 'tax_amount')) {
                $table->decimal('tax_amount', 15, 2)->default(0)->after('dpp_amount');
            }
            if (!Schema::hasColumn('purchase_orders', 'subtotal_bruto')) {
                $table->decimal('subtotal_bruto', 15, 2)->default(0)->after('tax_amount');
            }
            if (!Schema::hasColumn('purchase_orders', 'extra_discount')) {
                $table->decimal('extra_discount', 15, 2)->default(0)->after('subtotal_bruto');
            }
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_order_items', 'unit_name')) {
                $table->string('unit_name')->default('pcs')->after('product_id');
            }
            if (!Schema::hasColumn('purchase_order_items', 'conversion_qty')) {
                $table->integer('conversion_qty')->default(1)->after('unit_name');
            }
            if (!Schema::hasColumn('purchase_order_items', 'gross_price')) {
                $table->decimal('gross_price', 15, 2)->default(0)->after('qty');
            }
            if (!Schema::hasColumn('purchase_order_items', 'discount_percent_1')) {
                $table->decimal('discount_percent_1', 5, 2)->default(0)->after('gross_price');
            }
            if (!Schema::hasColumn('purchase_order_items', 'discount_percent_2')) {
                $table->decimal('discount_percent_2', 5, 2)->default(0)->after('discount_percent_1');
            }
            if (!Schema::hasColumn('purchase_order_items', 'discount_amount')) {
                $table->decimal('discount_amount', 15, 2)->default(0)->after('discount_percent_2');
            }
            if (!Schema::hasColumn('purchase_order_items', 'net_unit_price')) {
                $table->decimal('net_unit_price', 15, 2)->default(0)->after('discount_amount');
            }
            if (!Schema::hasColumn('purchase_order_items', 'final_cost_per_piece')) {
                $table->decimal('final_cost_per_piece', 15, 2)->default(0)->after('total_price');
            }
        });

        Schema::table('goods_receipts', function (Blueprint $table) {
            if (!Schema::hasColumn('goods_receipts', 'invoice_number_supplier')) {
                $table->string('invoice_number_supplier')->nullable()->after('receipt_number');
            }
            if (!Schema::hasColumn('goods_receipts', 'tax_type')) {
                $table->enum('tax_type', ['include', 'exclude', 'none'])->default('include')->after('notes');
            }
            if (!Schema::hasColumn('goods_receipts', 'tax_percentage')) {
                $table->decimal('tax_percentage', 5, 2)->default(11.00)->after('tax_type');
            }
            if (!Schema::hasColumn('goods_receipts', 'dpp_amount')) {
                $table->decimal('dpp_amount', 15, 2)->default(0)->after('tax_percentage');
            }
            if (!Schema::hasColumn('goods_receipts', 'tax_amount')) {
                $table->decimal('tax_amount', 15, 2)->default(0)->after('dpp_amount');
            }
            if (!Schema::hasColumn('goods_receipts', 'subtotal_bruto')) {
                $table->decimal('subtotal_bruto', 15, 2)->default(0)->after('tax_amount');
            }
            if (!Schema::hasColumn('goods_receipts', 'extra_discount')) {
                $table->decimal('extra_discount', 15, 2)->default(0)->after('subtotal_bruto');
            }
            if (!Schema::hasColumn('goods_receipts', 'total_amount')) {
                $table->decimal('total_amount', 15, 2)->default(0)->after('extra_discount');
            }
        });

        Schema::table('goods_receipt_items', function (Blueprint $table) {
            if (!Schema::hasColumn('goods_receipt_items', 'unit_name')) {
                $table->string('unit_name')->default('pcs')->after('product_branch_id');
            }
            if (!Schema::hasColumn('goods_receipt_items', 'conversion_qty')) {
                $table->integer('conversion_qty')->default(1)->after('unit_name');
            }
            if (!Schema::hasColumn('goods_receipt_items', 'gross_price')) {
                $table->decimal('gross_price', 15, 2)->default(0)->after('qty_received');
            }
            if (!Schema::hasColumn('goods_receipt_items', 'discount_percent_1')) {
                $table->decimal('discount_percent_1', 5, 2)->default(0)->after('gross_price');
            }
            if (!Schema::hasColumn('goods_receipt_items', 'discount_percent_2')) {
                $table->decimal('discount_percent_2', 5, 2)->default(0)->after('discount_percent_1');
            }
            if (!Schema::hasColumn('goods_receipt_items', 'discount_amount')) {
                $table->decimal('discount_amount', 15, 2)->default(0)->after('discount_percent_2');
            }
            if (!Schema::hasColumn('goods_receipt_items', 'net_unit_price')) {
                $table->decimal('net_unit_price', 15, 2)->default(0)->after('discount_amount');
            }
            if (!Schema::hasColumn('goods_receipt_items', 'final_cost_per_piece')) {
                $table->decimal('final_cost_per_piece', 15, 2)->default(0)->after('price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_number_supplier', 'due_date', 'tax_type', 'tax_percentage',
                'dpp_amount', 'tax_amount', 'subtotal_bruto', 'extra_discount'
            ]);
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn([
                'unit_name', 'conversion_qty', 'gross_price', 'discount_percent_1',
                'discount_percent_2', 'discount_amount', 'net_unit_price', 'final_cost_per_piece'
            ]);
        });

        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_number_supplier', 'tax_type', 'tax_percentage', 'dpp_amount',
                'tax_amount', 'subtotal_bruto', 'extra_discount', 'total_amount'
            ]);
        });

        Schema::table('goods_receipt_items', function (Blueprint $table) {
            $table->dropColumn([
                'unit_name', 'conversion_qty', 'gross_price', 'discount_percent_1',
                'discount_percent_2', 'discount_amount', 'net_unit_price', 'final_cost_per_piece'
            ]);
        });
    }
};
