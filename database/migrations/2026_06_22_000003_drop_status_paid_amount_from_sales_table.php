<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['status', 'paid_amount']);
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->enum('status', ['DRAFT', 'RECEIVED', 'PAID', 'PARTIALLY_PAID'])->default('DRAFT');
            $table->unsignedInteger('paid_amount')->default(0);
        });
    }
};
