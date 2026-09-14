<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deduction_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['potongan', 'tunjangan'])->default('potongan');
            $table->decimal('amount', 15, 2)->default(0);
            $table->boolean('is_percentage')->default(false);
            $table->enum('status', ['Aktif', 'Nonaktif'])->default('Aktif');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deduction_types');
    }
};
