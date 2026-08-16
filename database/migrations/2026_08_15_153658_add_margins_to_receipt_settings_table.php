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
        Schema::table('receipt_settings', function (Blueprint $table) {
            $table->integer('margin_top')->default(0)->after('is_default');
            $table->integer('margin_bottom')->default(0)->after('margin_top');
            $table->integer('margin_left')->default(0)->after('margin_bottom');
            $table->integer('margin_right')->default(0)->after('margin_left');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receipt_settings', function (Blueprint $table) {
            $table->dropColumn(['margin_top', 'margin_bottom', 'margin_left', 'margin_right']);
        });
    }
};
