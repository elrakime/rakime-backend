<?php

namespace App\Providers;

use App\Models\InstallmentCashPayment;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchasePayment;
use App\Models\PurchaseReturn;
use App\Models\RestockOrder;
use App\Models\Sale;
use App\Models\Transfer;
use App\Models\TransferItem;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Relation::morphMap([
            // source_type in stocks
            'purchase_item'  => PurchaseItem::class,
            'transfer_item'  => TransferItem::class,

            // reference_type in treasury_movements
            'installment_cash_payments' => InstallmentCashPayment::class,
            'purchase_payments'         => PurchasePayment::class,
            'purchase_returns'          => PurchaseReturn::class,
            'transfers'                 => Transfer::class,
            'sales'                     => Sale::class,
            'purchases'                 => Purchase::class,
            'restock_orders'            => RestockOrder::class,
        ]);
    }
}
