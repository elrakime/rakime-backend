<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->unsignedInteger('gross_amount')->after('total_amount');
            $table->unsignedInteger('tax_rate')->nullable()->after('gross_amount');
            $table->unsignedInteger('tax_amount')->default(0)->after('tax_rate');
            $table->string('discount_type')->nullable()->after('tax_amount');
            $table->unsignedInteger('discount_value')->nullable()->after('discount_type');
            $table->unsignedInteger('discount_amount')->default(0)->after('discount_value');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['gross_amount', 'tax_rate', 'tax_amount', 'discount_type', 'discount_value', 'discount_amount']);
        });
    }
};
