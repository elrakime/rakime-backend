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
            $table->unsignedInteger('gross_amount');
            $table->unsignedInteger('total_amount');
            $table->unsignedInteger('tax_rate')->nullable();
            $table->unsignedInteger('tax_amount')->default(0);
            $table->string('discount_type')->nullable();
            $table->unsignedInteger('discount_value')->nullable();
            $table->unsignedInteger('discount_amount')->default(0);
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
