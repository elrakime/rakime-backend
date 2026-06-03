<?php

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installment_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('installment_contracts');
            $table->string('reference')->unique();
            $table->unsignedSmallInteger('subscription_number');
            $table->unsignedInteger('amount');
            $table->unsignedSmallInteger('total_months');
            $table->enum('status', SubscriptionStatus::keys())->default(SubscriptionStatus::default()->value);
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installment_subscriptions');
    }
};
