<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // source_id is polymorphic (purchase_item or transfer_item) — no FK constraint
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained();
            $table->foreignId('product_id')->constrained();
            $table->unsignedBigInteger('source_id');
            $table->string('source_type');
            $table->unsignedInteger('initial_quantity');
            $table->unsignedInteger('current_quantity');
            $table->unsignedInteger('purchase_price');
            $table->unsignedInteger('selling_price');
            $table->unsignedInteger('installment_price');
            $table->timestamp('purchased_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
