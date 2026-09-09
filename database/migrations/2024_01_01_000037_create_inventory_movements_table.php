<?php

use App\Enums\InventoryMovementType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // source_id is polymorphic — movement_type determines the referenced table
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained();
            $table->foreignId('inventory_id')->constrained();
            $table->foreignId('product_id')->constrained();
            $table->nullableMorphs('source');
            $table->enum('movement_type', InventoryMovementType::keys());
            $table->integer('old_quantity')->nullable();
            $table->integer('new_quantity')->nullable();
            $table->integer('quantity');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
