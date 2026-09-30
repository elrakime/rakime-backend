<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stats', function (Blueprint $table) {
            $table->id();
            $table->date('month');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->json('data');
            $table->timestamps();

            $table->unique(['month', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stats');
    }
};
