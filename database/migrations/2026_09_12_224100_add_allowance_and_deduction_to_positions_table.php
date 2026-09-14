<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAllowanceAndDeductionToPositionsTable extends Migration
{
    public function up()
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->decimal('default_allowance', 15, 2)->default(0);
            $table->decimal('default_deduction', 15, 2)->default(0);
        });
    }

    public function down()
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->dropColumn(['default_allowance', 'default_deduction']);
        });
    }
}
