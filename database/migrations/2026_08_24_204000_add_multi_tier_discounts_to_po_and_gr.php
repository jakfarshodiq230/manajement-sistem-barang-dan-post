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
        if (Schema::hasTable('purchase_order_items')) {
            Schema::table('purchase_order_items', function (Blueprint $table) {
                if (!Schema::hasColumn('purchase_order_items', 'discount_percent_3')) {
                    $table->decimal('discount_percent_3', 5, 2)->default(0)->after('discount_percent_2');
                }
                if (!Schema::hasColumn('purchase_order_items', 'discount_percent_4')) {
                    $table->decimal('discount_percent_4', 5, 2)->default(0)->after('discount_percent_3');
                }
                if (!Schema::hasColumn('purchase_order_items', 'discount_percent_5')) {
                    $table->decimal('discount_percent_5', 5, 2)->default(0)->after('discount_percent_4');
                }
                if (!Schema::hasColumn('purchase_order_items', 'discount_string')) {
                    $table->string('discount_string')->nullable()->after('discount_percent_5');
                }
            });
        }

        if (Schema::hasTable('goods_receipt_items')) {
            Schema::table('goods_receipt_items', function (Blueprint $table) {
                if (!Schema::hasColumn('goods_receipt_items', 'discount_percent_3')) {
                    $table->decimal('discount_percent_3', 5, 2)->default(0)->nullable();
                }
                if (!Schema::hasColumn('goods_receipt_items', 'discount_percent_4')) {
                    $table->decimal('discount_percent_4', 5, 2)->default(0)->nullable();
                }
                if (!Schema::hasColumn('goods_receipt_items', 'discount_percent_5')) {
                    $table->decimal('discount_percent_5', 5, 2)->default(0)->nullable();
                }
                if (!Schema::hasColumn('goods_receipt_items', 'discount_string')) {
                    $table->string('discount_string')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('purchase_order_items')) {
            Schema::table('purchase_order_items', function (Blueprint $table) {
                $cols = ['discount_percent_3', 'discount_percent_4', 'discount_percent_5', 'discount_string'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('purchase_order_items', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('goods_receipt_items')) {
            Schema::table('goods_receipt_items', function (Blueprint $table) {
                $cols = ['discount_percent_3', 'discount_percent_4', 'discount_percent_5', 'discount_string'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('goods_receipt_items', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
