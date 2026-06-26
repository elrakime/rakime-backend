<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rename transfers → inventory_transfers
        Schema::rename('transfers', 'inventory_transfers');
        Schema::table('inventory_transfers', function (Blueprint $table) {
            $table->foreignId('performed_by')->nullable()->after('to_inventory_id')->constrained('users');
        });

        // Rename transfer_items → inventory_transfer_items
        Schema::rename('transfer_items', 'inventory_transfer_items');
        Schema::table('inventory_transfer_items', function (Blueprint $table) {
            $table->dropForeign(['transfer_id']);
            $table->renameColumn('transfer_id', 'inventory_transfer_id');
            $table->foreign('inventory_transfer_id')->references('id')->on('inventory_transfers');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_transfer_items', function (Blueprint $table) {
            $table->dropForeign(['inventory_transfer_id']);
            $table->renameColumn('inventory_transfer_id', 'transfer_id');
            $table->foreign('transfer_id')->references('id')->on('inventory_transfers');
        });

        Schema::rename('inventory_transfer_items', 'transfer_items');

        Schema::table('inventory_transfers', function (Blueprint $table) {
            $table->dropForeign(['performed_by']);
            $table->dropColumn('performed_by');
        });

        Schema::rename('inventory_transfers', 'transfers');
    }
};
