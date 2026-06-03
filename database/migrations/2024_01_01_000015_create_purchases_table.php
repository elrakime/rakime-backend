<?php

use App\Enums\PurchaseStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained();
            $table->string('reference');
            $table->enum('status', PurchaseStatus::keys())->default(PurchaseStatus::default()->value);
            $table->unsignedInteger('total_amount');
            $table->unsignedInteger('paid_amount')->default(0);
            $table->string('note');
            $table->timestamp('purchased_at');
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
