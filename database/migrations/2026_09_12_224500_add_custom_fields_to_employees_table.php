<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomFieldsToEmployeesTable extends Migration
{
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('custom_base_salary', 15, 2)->nullable();
            $table->decimal('custom_allowance', 15, 2)->nullable();
            $table->decimal('custom_deduction', 15, 2)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('attendance_machine_id')->nullable();
        });
    }

    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'custom_base_salary',
                'custom_allowance',
                'custom_deduction',
                'bank_name',
                'bank_account_number',
                'attendance_machine_id'
            ]);
        });
    }
}
