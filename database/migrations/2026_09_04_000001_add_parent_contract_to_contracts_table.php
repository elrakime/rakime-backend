<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->foreignId('parent_contract_id')
                ->nullable()
                ->after('id')
                ->constrained('contracts')
                ->nullOnDelete();

            $table->timestamp('extended_at')->nullable()->after('parent_contract_id');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_contract_id');
            $table->dropColumn('extended_at');
        });
    }
};
