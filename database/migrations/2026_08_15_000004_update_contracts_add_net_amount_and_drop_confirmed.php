<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->unsignedInteger('net_amount')->nullable()->after('total_amount');
        });

        DB::statement("ALTER TABLE contracts MODIFY status ENUM('pending','approved','rejected','configured','active','completed','closed','cancelled') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE contracts MODIFY status ENUM('pending','approved','rejected','confirmed','configured','active','completed','closed','cancelled') NOT NULL DEFAULT 'pending'");

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn('net_amount');
        });
    }
};
