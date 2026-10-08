<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_stats', function (Blueprint $table) {
            $table->id();
            $table->date('month');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->json('data');
            $table->timestamps();

            $table->unique(['month', 'branch_id']);
        });

        Schema::create('contract_stats', function (Blueprint $table) {
            $table->id();
            $table->date('month');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->json('data');
            $table->timestamps();

            $table->unique(['month', 'branch_id', 'account_id']);
        });

        Schema::dropIfExists('stats');
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_stats');
        Schema::dropIfExists('sale_stats');

        Schema::create('stats', function (Blueprint $table) {
            $table->id();
            $table->date('month');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->json('data');
            $table->timestamps();

            $table->unique(['month', 'branch_id']);
        });
    }
};
