<?php

use App\Enums\ContractStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('client_id')->constrained();
            $table->foreignId('account_id')->constrained();
            $table->foreignId('branch_id')->constrained();
            $table->string('reference')->nullable();
            $table->enum('status', ContractStatus::keys())->default(ContractStatus::default()->value);
            $table->unsignedInteger('max_amount')->nullable();
            $table->unsignedInteger('advance_amount')->nullable();
            $table->unsignedSmallInteger('months_count')->nullable();
            $table->unsignedInteger('total_amount')->nullable();
            $table->unsignedInteger('monthly_amount')->nullable();
            $table->string('note')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
