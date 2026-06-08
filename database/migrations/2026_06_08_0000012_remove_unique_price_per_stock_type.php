<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prices', function (Blueprint $table) {
            $table->dropForeign(['stock_id']);
            $table->dropUnique(['stock_id', 'type']);
            $table->foreign('stock_id')->references('id')->on('stocks')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('prices', function (Blueprint $table) {
            $table->dropForeign(['stock_id']);
            $table->unique(['stock_id', 'type']);
            $table->foreign('stock_id')->references('id')->on('stocks')->cascadeOnDelete();
        });
    }
};
