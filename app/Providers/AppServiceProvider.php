<?php

namespace App\Providers;

use App\Models\InstallmentPayment;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchasePayment;
use App\Models\PurchaseReturn;
use App\Models\Restock;
use App\Models\Sale;
use App\Models\InventoryTransfer;
use App\Models\InventoryTransferItem;
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
            'inventory_transfer_item'  => InventoryTransferItem::class,

            // source_type in wallet_movements
            'installment_payments'      => InstallmentPayment::class,
            'purchase_payments'         => PurchasePayment::class,
            'purchase_returns'          => PurchaseReturn::class,
            'inventory_transfers'       => InventoryTransfer::class,
            'sales'                     => Sale::class,
            'purchases'                 => Purchase::class,
            'restocks'                  => Restock::class,
        ]);
    }
}
