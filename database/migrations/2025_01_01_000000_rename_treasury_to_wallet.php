<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rename treasuries table to wallets
        Schema::rename('treasuries', 'wallets');

        // Drop branch_id FK + column, add polymorphic owner columns
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
            $table->nullableMorphs('owner');
        });

        // Rename treasury_movements table to wallet_movements
        Schema::rename('treasury_movements', 'wallet_movements');

        // Rename treasury_id to wallet_id
        Schema::table('wallet_movements', function (Blueprint $table) {
            $table->dropForeign(['treasury_id']);
            $table->renameColumn('treasury_id', 'wallet_id');
            $table->foreign('wallet_id')->references('id')->on('wallets');
        });
    }

    public function down(): void
    {
        Schema::table('wallet_movements', function (Blueprint $table) {
            $table->dropForeign(['wallet_id']);
            $table->renameColumn('wallet_id', 'treasury_id');
            $table->foreign('treasury_id')->references('id')->on('wallets');
        });

        Schema::rename('wallet_movements', 'treasury_movements');

        Schema::table('wallets', function (Blueprint $table) {
            $table->dropMorphs('owner');
            $table->foreignId('branch_id')->nullable()->constrained();
        });

        Schema::rename('wallets', 'treasuries');
    }
};
