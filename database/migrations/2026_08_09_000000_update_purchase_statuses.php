<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Migrate existing rows to new statuses
        DB::table('purchases')
            ->whereIn('status', ['received', 'paid', 'partially_paid'])
            ->update(['status' => 'completed']);

        // Alter the enum column to the new values
        DB::statement("ALTER TABLE purchases MODIFY COLUMN status ENUM('pending', 'completed', 'canceled') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE purchases MODIFY COLUMN status ENUM('pending', 'received', 'paid', 'partially_paid') NOT NULL DEFAULT 'pending'");
    }
};
