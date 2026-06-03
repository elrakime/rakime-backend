<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ccp_number')->unique();
            $table->string('ccp_key');
            $table->unsignedTinyInteger('draw_day');
            $table->unsignedInteger('min_withdraw_amount');
            $table->unsignedInteger('max_withdraw_count');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
