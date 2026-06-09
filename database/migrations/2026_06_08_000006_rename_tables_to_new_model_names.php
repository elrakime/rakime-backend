<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('product_expirations', 'expirations');
        Schema::rename('product_expiration_items', 'expiration_items');
        Schema::rename('restock_orders', 'restocks');
        Schema::rename('restock_order_items', 'restock_items');
        Schema::rename('installment_cash_payments', 'installment_payments');
        Schema::rename('installment_contracts', 'contracts');
        Schema::rename('installment_contract_items', 'contract_items');
        Schema::rename('installment_draws', 'draws');
        Schema::rename('installment_subscriptions', 'subscriptions');
    }

    public function down(): void
    {
        Schema::rename('subscriptions', 'installment_subscriptions');
        Schema::rename('draws', 'installment_draws');
        Schema::rename('contract_items', 'installment_contract_items');
        Schema::rename('contracts', 'installment_contracts');
        Schema::rename('installment_payments', 'installment_cash_payments');
        Schema::rename('restock_items', 'restock_order_items');
        Schema::rename('restocks', 'restock_orders');
        Schema::rename('expiration_items', 'product_expiration_items');
        Schema::rename('expirations', 'product_expirations');
    }
};
