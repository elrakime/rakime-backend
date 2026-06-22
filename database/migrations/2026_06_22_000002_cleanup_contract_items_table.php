<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_items', function (Blueprint $table) {
            $table->dropForeign(['batch_id']);
            $table->dropForeign(['price_id']);
            $table->dropColumn(['batch_id', 'price_id', 'unit_price_snapshot', 'installment_price_snapshot']);
            $table->renameColumn('unit_price', 'price');
        });
    }

    public function down(): void
    {
        Schema::table('contract_items', function (Blueprint $table) {
            $table->renameColumn('price', 'unit_price');
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('price_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('unit_price_snapshot');
            $table->unsignedInteger('installment_price_snapshot');
        });
    }
};
