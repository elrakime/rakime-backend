<?php

use App\Enums\PurchaseStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained();
            $table->foreignId('branch_id')->constrained();
            $table->foreignId('inventory_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference');
            $table->enum('status', PurchaseStatus::keys())->default(PurchaseStatus::default()->value);
            $table->decimal('total_amount', 15, 2);
            $table->decimal('net_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
