<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts');
            $table->foreignId('product_id')->constrained();
            $table->foreignId('stock_id')->constrained();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('price_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('unit_price');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('unit_price_snapshot');
            $table->unsignedInteger('installment_price_snapshot');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_items');
    }
};
