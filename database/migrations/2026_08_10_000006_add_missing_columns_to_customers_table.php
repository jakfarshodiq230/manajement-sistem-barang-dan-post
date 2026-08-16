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
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'email')) {
                $table->string('email')->nullable();
            }
            if (!Schema::hasColumn('customers', 'nik')) {
                $table->string('nik', 50)->nullable();
            }
            if (!Schema::hasColumn('customers', 'company_name')) {
                $table->string('company_name')->nullable();
            }
            if (!Schema::hasColumn('customers', 'city')) {
                $table->string('city', 100)->nullable();
            }
            if (!Schema::hasColumn('customers', 'province')) {
                $table->string('province', 100)->nullable();
            }
            if (!Schema::hasColumn('customers', 'notes')) {
                $table->text('notes')->nullable();
            }
            if (!Schema::hasColumn('customers', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'email',
                'nik',
                'company_name',
                'city',
                'province',
                'notes',
                'is_active'
            ]);
        });
    }
};
