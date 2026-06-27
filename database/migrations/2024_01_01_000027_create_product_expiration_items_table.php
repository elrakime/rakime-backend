<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expiration_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expiration_id')->constrained('expirations');
            $table->foreignId('stock_id')->constrained();
            $table->unsignedInteger('quantity');
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expiration_items');
    }
};
