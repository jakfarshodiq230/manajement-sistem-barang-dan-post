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
        $tables = [
            'purchase_orders',
            'goods_receipts',
            'return_transactions',
            'sales'
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table_blueprint) use ($table) {
                if (!Schema::hasColumn($table, 'uuid')) {
                    $table_blueprint->uuid('uuid')->nullable()->unique();
                }
                if (!Schema::hasColumn($table, 'validated_by')) {
                    $table_blueprint->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn($table, 'validated_at')) {
                    $table_blueprint->timestamp('validated_at')->nullable();
                }
                if (!Schema::hasColumn($table, 'approved_by')) {
                    $table_blueprint->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn($table, 'approved_at')) {
                    $table_blueprint->timestamp('approved_at')->nullable();
                }
                if (!Schema::hasColumn($table, 'approval_status')) {
                    $table_blueprint->string('approval_status')->default('pending'); // pending, validated, approved, rejected
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'purchase_orders',
            'goods_receipts',
            'return_transactions',
            'sales'
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropForeign(['validated_by']);
                $table->dropForeign(['approved_by']);
                $table->dropColumn([
                    'uuid',
                    'validated_by',
                    'validated_at',
                    'approved_by',
                    'approved_at',
                    'approval_status'
                ]);
            });
        }
    }
};
