<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_transfers', 'uuid')) {
                $table->uuid('uuid')->nullable()->after('id')->index();
            }
        });

        // Populate existing stock transfers with UUID
        $transfers = DB::table('stock_transfers')->whereNull('uuid')->orWhere('uuid', '')->get();
        foreach ($transfers as $t) {
            DB::table('stock_transfers')->where('id', $t->id)->update(['uuid' => (string) Str::uuid()]);
        }
    }

    public function down(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            if (Schema::hasColumn('stock_transfers', 'uuid')) {
                $table->dropColumn('uuid');
            }
        });
    }
};
