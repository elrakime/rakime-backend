<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropForeign(['batch_id']);
            $table->dropForeign(['price_id']);
            $table->dropColumn(['batch_id', 'price_id']);
            $table->renameColumn('unit_price', 'price');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->renameColumn('price', 'unit_price');
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('price_id')->nullable()->constrained()->nullOnDelete();
        });
    }
};
