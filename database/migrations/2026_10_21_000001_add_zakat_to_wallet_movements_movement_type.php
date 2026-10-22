<?php

use App\Enums\WalletMovementType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_movements', function (Blueprint $table) {
            $table->enum('movement_type', WalletMovementType::keys())->change();
        });
    }

    public function down(): void
    {
        DB::table('wallet_movements')
            ->where('movement_type', WalletMovementType::ZAKAT->value)
            ->update(['movement_type' => WalletMovementType::WITHDRAWAL->value]);

        Schema::table('wallet_movements', function (Blueprint $table) {
            $table->enum(
                'movement_type',
                array_values(array_diff(WalletMovementType::keys(), [WalletMovementType::ZAKAT->value])),
            )->change();
        });
    }
};
