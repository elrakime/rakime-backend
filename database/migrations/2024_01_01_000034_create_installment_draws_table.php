<?php

use App\Enums\DrawStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('draws', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions');
            $table->foreignId('installment_id')->constrained();
            $table->decimal('amount', 15, 2);
            $table->enum('status', DrawStatus::keys())->nullable();
            $table->date('due_date');
            $table->date('last_attempted_at')->nullable();
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('draws');
    }
};
