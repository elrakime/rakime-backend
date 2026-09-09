<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference');
            $table->decimal('gross_amount', 15, 2);
            $table->decimal('total_amount', 15, 2);
            $table->decimal('tax_rate', 15, 2)->nullable();
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->string('discount_type')->nullable();
            $table->decimal('discount_value', 15, 2)->nullable();
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
