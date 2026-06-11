<?php

use App\Enums\InventoryMovementType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // moveable_id is polymorphic — movement_type determines the referenced table
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_id')->constrained();
            $table->foreignId('product_id')->constrained();
            $table->unsignedBigInteger('moveable_id');
            $table->enum('movement_type', InventoryMovementType::keys());
            $table->integer('quantity');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
