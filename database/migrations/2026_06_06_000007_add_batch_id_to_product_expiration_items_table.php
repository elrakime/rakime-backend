<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_expiration_items', function (Blueprint $table) {
            $table->foreignId('batch_id')->after('stock_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('product_expiration_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('batch_id');
        });
    }
};
