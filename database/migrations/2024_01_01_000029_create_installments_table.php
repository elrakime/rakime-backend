<?php

use App\Enums\InstallmentPaymentMethod;
use App\Enums\InstallmentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('installment_contracts');
            $table->unsignedSmallInteger('month_number');
            $table->unsignedInteger('amount');
            $table->enum('status', InstallmentStatus::keys())->default(InstallmentStatus::default()->value);
            $table->enum('payment_method', InstallmentPaymentMethod::keys());
            $table->date('due_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installments');
    }
};
