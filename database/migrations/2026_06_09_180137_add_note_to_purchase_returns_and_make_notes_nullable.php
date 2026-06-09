<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add note to purchase_returns
        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->string('note')->nullable()->after('reference');
        });

        // Make note nullable in purchases
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('note')->nullable()->change();
        });

        // Make note nullable in transfers
        Schema::table('transfers', function (Blueprint $table) {
            $table->string('note')->nullable()->change();
        });

        // Make note nullable in sales
        Schema::table('sales', function (Blueprint $table) {
            $table->string('note')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->dropColumn('note');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->string('note')->nullable(false)->change();
        });

        Schema::table('transfers', function (Blueprint $table) {
            $table->string('note')->nullable(false)->change();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->string('note')->nullable(false)->change();
        });
    }
};
