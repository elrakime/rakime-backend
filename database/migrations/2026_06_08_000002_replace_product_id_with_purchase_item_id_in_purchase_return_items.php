<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
        });

        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->foreignId('purchase_item_id')->after('purchase_return_id')->constrained('purchase_items')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->dropForeign(['purchase_item_id']);
            $table->dropColumn('purchase_item_id');
        });

        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->foreignId('product_id')->after('purchase_return_id')->constrained()->cascadeOnDelete();
        });
    }
};
