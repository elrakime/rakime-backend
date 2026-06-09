<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expiration_items', function (Blueprint $table) {
            $table->dropForeign('product_expiration_items_product_id_foreign');
            $table->dropColumn('product_id');
        });

        Schema::table('expirations', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable()->after('reported_at');
        });
    }

    public function down(): void
    {
        Schema::table('expirations', function (Blueprint $table) {
            $table->dropColumn('approved_at');
        });

        Schema::table('expiration_items', function (Blueprint $table) {
            $table->foreignId('product_id')->after('expiration_id')->constrained();
        });
    }
};
