<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restocks', function (Blueprint $table) {
            $table->nullableMorphs('fulfilled_with');
        });
    }

    public function down(): void
    {
        Schema::table('restocks', function (Blueprint $table) {
            $table->dropMorphs('fulfilled_with');
        });
    }
};
