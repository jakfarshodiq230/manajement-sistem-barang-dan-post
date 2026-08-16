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
        if (!Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->string('name');
                $table->string('phone')->nullable();
                $table->text('address')->nullable();
                $table->string('email')->nullable();
                $table->string('nik', 50)->nullable();
                $table->string('company_name')->nullable();
                $table->string('city', 100)->nullable();
                $table->string('province', 100)->nullable();
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->decimal('credit_limit', 15, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
