<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn([
                'source_id',
                'source_type',
                'initial_quantity',
                'current_quantity',
                'purchase_price',
                'selling_price',
                'installment_price',
                'purchased_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->unsignedBigInteger('source_id');
            $table->string('source_type');
            $table->unsignedInteger('initial_quantity');
            $table->unsignedInteger('current_quantity');
            $table->unsignedInteger('purchase_price');
            $table->unsignedInteger('selling_price');
            $table->unsignedInteger('installment_price');
            $table->timestamp('purchased_at');
        });
    }
};
