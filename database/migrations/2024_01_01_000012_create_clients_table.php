<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained();
            $table->foreignId('wilaya_id')->constrained();
            $table->string('name');
            $table->string('phone')->unique();
            $table->date('birthdate')->nullable();
            $table->string('address')->nullable();
            $table->string('occupation')->nullable();
            $table->string('employer')->nullable();
            $table->decimal('salary', 15, 2);
            $table->string('nin')->unique();
            $table->string('ccp_number')->unique();
            $table->string('ccp_key');
            $table->string('eccp')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
