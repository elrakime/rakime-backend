<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('net_profit', 15, 2)->default(0)->after('purchase_cost');
        });

        DB::table('sales')->update([
            'net_profit' => DB::raw('total_amount - purchase_cost'),
        ]);
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('net_profit');
        });
    }
};
