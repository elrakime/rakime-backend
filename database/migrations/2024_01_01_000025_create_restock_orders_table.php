<?php

use App\Enums\RestockOrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restock_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('branch_id')->constrained();
            $table->string('reference');
            $table->enum('status', RestockOrderStatus::keys())->default(RestockOrderStatus::default()->value);
            $table->string('note')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restock_orders');
    }
};
