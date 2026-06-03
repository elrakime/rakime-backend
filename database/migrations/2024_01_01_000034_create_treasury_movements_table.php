<?php

use App\Enums\TreasuryMovementType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treasury_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treasury_id')->constrained('treasuries');
            $table->enum('movement_type', TreasuryMovementType::keys());
            $table->decimal('amount', 15, 2);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('note')->nullable();
            $table->foreignId('performed_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treasury_movements');
    }
};
