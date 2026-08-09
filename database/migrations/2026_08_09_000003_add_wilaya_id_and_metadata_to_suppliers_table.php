<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->foreignId('wilaya_id')->nullable()->constrained()->nullOnDelete();
            $table->json('metadata')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropForeign(['wilaya_id']);
            $table->dropColumn(['wilaya_id', 'metadata']);
        });
    }
};
