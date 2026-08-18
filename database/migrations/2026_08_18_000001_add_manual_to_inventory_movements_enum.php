<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE inventory_movements MODIFY COLUMN movement_type ENUM('receive','return','transfer_in','transfer_out','sale','expired','restock_received','transfer_cancel','sale_return','sale_update','manual') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE inventory_movements MODIFY COLUMN movement_type ENUM('receive','return','transfer_in','transfer_out','sale','expired','restock_received','transfer_cancel','sale_return','sale_update') NOT NULL");
    }
};
