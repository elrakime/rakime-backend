<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_expiration_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expiration_id')->constrained('product_expirations');
            $table->foreignId('product_id')->constrained();
            $table->foreignId('stock_id')->constrained();
            $table->unsignedInteger('quantity');
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_expiration_items');
    }
};
