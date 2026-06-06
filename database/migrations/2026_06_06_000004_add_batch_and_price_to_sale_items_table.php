<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->foreignId('batch_id')->after('stock_id')->constrained()->cascadeOnDelete();
            $table->foreignId('price_id')->after('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('unit_price')->after('price_id');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('batch_id');
            $table->dropConstrainedForeignId('price_id');
            $table->dropColumn('unit_price');
        });
    }
};
