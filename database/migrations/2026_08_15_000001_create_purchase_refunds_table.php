<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\PurchaseRefundStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained();
            $table->foreignId('purchase_return_id')->constrained();
            $table->unsignedInteger('amount');
            $table->string('status')->default(PurchaseRefundStatus::PAID->value);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_refunds');
    }
};
