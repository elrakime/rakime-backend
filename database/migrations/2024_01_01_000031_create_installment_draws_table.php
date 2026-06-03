<?php

use App\Enums\DrawStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installment_draws', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('installment_subscriptions');
            $table->foreignId('installment_id')->constrained();
            $table->unsignedSmallInteger('month_number');
            $table->unsignedInteger('amount');
            $table->enum('status', DrawStatus::keys())->default(DrawStatus::default()->value);
            $table->date('scheduled_date');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installment_draws');
    }
};
