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
            $table->foreignId('parent_contract_id')->nullable()->constrained('contracts')->nullOnDelete();
            $table->timestamp('extended_at')->nullable();
            $table->foreignId('client_id')->constrained();
            $table->foreignId('account_id')->constrained();
            $table->foreignId('branch_id')->constrained();
            $table->string('reference')->nullable();
            $table->enum('status', ContractStatus::keys())->default(ContractStatus::default()->value);
            $table->decimal('max_amount', 15, 2)->nullable();
            $table->decimal('advance_amount', 15, 2)->nullable();
            $table->unsignedSmallInteger('months_count')->nullable();
            $table->decimal('total_amount', 15, 2)->nullable();
            $table->decimal('net_amount', 15, 2)->nullable();
            $table->decimal('monthly_amount', 15, 2)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
