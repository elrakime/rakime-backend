<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill contracts.draw_date from the first subscription of each contract,
        // so existing configured contracts retain their anchor date.
        DB::statement(
            'UPDATE contracts c
             JOIN (
                 SELECT contract_id, MIN(draw_date) AS draw_date
                 FROM subscriptions
                 WHERE draw_date IS NOT NULL
                 GROUP BY contract_id
             ) s ON s.contract_id = c.id
             SET c.draw_date = s.draw_date
             WHERE c.draw_date IS NULL'
        );

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['total_months', 'draw_date']);
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedSmallInteger('total_months')->after('amount');
            $table->date('draw_date')->nullable()->after('total_months');
        });

        // Restore subscriptions.draw_date from the contract, best-effort.
        DB::statement(
            'UPDATE subscriptions s
             JOIN contracts c ON c.id = s.contract_id
             SET s.draw_date = c.draw_date
             WHERE c.draw_date IS NOT NULL'
        );
    }
};
