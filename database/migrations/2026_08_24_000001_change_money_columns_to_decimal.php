<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convert all integer-based money columns to decimal(15,2) to match the
     * existing wallet / financial-record convention.
     *
     * Existing values are stored as whole units (e.g. 1500 = 1500.00), so no
     * data scaling is required — only the column type changes.
     */
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->decimal('min_withdraw_amount', 15, 2)->nullable()->change();
        });

        Schema::table('batches', function (Blueprint $table) {
            $table->decimal('purchase_price', 15, 2)->change();
        });

        Schema::table('batch_allocations', function (Blueprint $table) {
            $table->decimal('purchase_price', 15, 2)->change();
        });

        Schema::table('prices', function (Blueprint $table) {
            $table->decimal('amount', 15, 2)->change();
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('total_amount', 15, 2)->change();
            $table->decimal('net_amount', 15, 2)->default(0)->change();
            $table->decimal('paid_amount', 15, 2)->default(0)->change();
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->decimal('price', 15, 2)->change();
        });

        Schema::table('purchase_payments', function (Blueprint $table) {
            $table->decimal('amount', 15, 2)->change();
        });

        Schema::table('purchase_refunds', function (Blueprint $table) {
            $table->decimal('amount', 15, 2)->change();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('gross_amount', 15, 2)->change();
            $table->decimal('total_amount', 15, 2)->change();
            $table->decimal('tax_rate', 15, 2)->nullable()->change();
            $table->decimal('tax_amount', 15, 2)->default(0)->change();
            $table->decimal('discount_value', 15, 2)->nullable()->change();
            $table->decimal('discount_amount', 15, 2)->default(0)->change();
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('price', 15, 2)->change();
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->decimal('max_amount', 15, 2)->nullable()->change();
            $table->decimal('advance_amount', 15, 2)->nullable()->change();
            $table->decimal('total_amount', 15, 2)->nullable()->change();
            $table->decimal('net_amount', 15, 2)->nullable()->change();
            $table->decimal('monthly_amount', 15, 2)->nullable()->change();
        });

        Schema::table('contract_items', function (Blueprint $table) {
            $table->decimal('price', 15, 2)->change();
        });

        Schema::table('installments', function (Blueprint $table) {
            $table->decimal('amount', 15, 2)->change();
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->decimal('amount', 15, 2)->change();
        });

        Schema::table('draws', function (Blueprint $table) {
            $table->decimal('amount', 15, 2)->change();
        });

        Schema::table('installment_payments', function (Blueprint $table) {
            $table->decimal('amount', 15, 2)->change();
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->unsignedInteger('min_withdraw_amount')->nullable()->change();
        });

        Schema::table('batches', function (Blueprint $table) {
            $table->unsignedInteger('purchase_price')->change();
        });

        Schema::table('batch_allocations', function (Blueprint $table) {
            $table->unsignedInteger('purchase_price')->change();
        });

        Schema::table('prices', function (Blueprint $table) {
            $table->unsignedInteger('amount')->change();
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->unsignedInteger('total_amount')->change();
            $table->unsignedInteger('net_amount')->default(0)->change();
            $table->unsignedInteger('paid_amount')->default(0)->change();
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->unsignedInteger('price')->change();
        });

        Schema::table('purchase_payments', function (Blueprint $table) {
            $table->unsignedInteger('amount')->change();
        });

        Schema::table('purchase_refunds', function (Blueprint $table) {
            $table->unsignedInteger('amount')->change();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->unsignedInteger('gross_amount')->change();
            $table->unsignedInteger('total_amount')->change();
            $table->unsignedInteger('tax_rate')->nullable()->change();
            $table->unsignedInteger('tax_amount')->default(0)->change();
            $table->unsignedInteger('discount_value')->nullable()->change();
            $table->unsignedInteger('discount_amount')->default(0)->change();
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->unsignedInteger('price')->change();
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->unsignedInteger('max_amount')->nullable()->change();
            $table->unsignedInteger('advance_amount')->nullable()->change();
            $table->unsignedInteger('total_amount')->nullable()->change();
            $table->unsignedInteger('net_amount')->nullable()->change();
            $table->unsignedInteger('monthly_amount')->nullable()->change();
        });

        Schema::table('contract_items', function (Blueprint $table) {
            $table->unsignedInteger('price')->change();
        });

        Schema::table('installments', function (Blueprint $table) {
            $table->unsignedInteger('amount')->change();
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedInteger('amount')->change();
        });

        Schema::table('draws', function (Blueprint $table) {
            $table->unsignedInteger('amount')->change();
        });

        Schema::table('installment_payments', function (Blueprint $table) {
            $table->unsignedInteger('amount')->change();
        });
    }
};
