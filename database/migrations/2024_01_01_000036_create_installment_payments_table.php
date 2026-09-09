<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installment_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('contract_payments')->cascadeOnDelete();
            $table->foreignId('installment_id')->constrained('installments')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['payment_id', 'installment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installment_payments');
    }
};
