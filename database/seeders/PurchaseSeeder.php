<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Wallet;
use App\Services\PurchasePaymentService;
use App\Services\PurchaseReturnService;
use App\Services\PurchaseService;
use App\Services\WalletService;
use Illuminate\Database\Seeder;

class PurchaseSeeder extends Seeder
{
    public function run(): void
    {
        $supplier  = Supplier::first();
        $inventory = Inventory::whereHas('branch', fn ($q) => $q->where('code', 'M'))->first();
        $products  = Product::take(3)->get();

        if (! $supplier || ! $inventory || $products->isEmpty()) {
            return;
        }

        $purchaseService = app(PurchaseService::class);
        $paymentService  = app(PurchasePaymentService::class);
        $returnService   = app(PurchaseReturnService::class);
        $walletService   = app(WalletService::class);

        // Fund the branch wallet so payments can be made.
        $wallet = Wallet::where('owner_type', Branch::class)
            ->where('owner_id', $inventory->branch_id)
            ->first();

        if ($wallet) {
            // Deposit enough to cover purchase 1 (1,400,000) + purchase 2 (100,000).
            $walletService->deposit($wallet, 2000000, 'Initial funding for purchases');
        }

        // ------ Purchase 1: received & fully paid ------
        $purchase1 = $purchaseService->create([
            'supplier_id' => $supplier->id,
            'branch_id'   => $inventory->branch_id,
            'note'        => 'First purchase order',
            'items' => $products->map(fn ($product, $i) => [
                'product_id' => $product->id,
                'quantity'   => 5 + $i,
                'price'      => 50000 + ($i * 25000),
            ])->all(),
        ]);

        // "Receive" the purchase — this creates batches, prices,
        // batch allocations and inventory movements via the service.
        $purchaseService->receive($purchase1, [
            'inventory_id' => $inventory->id,
            'items' => $products->map(fn ($product, $i) => [
                'product_id'         => $product->id,
                'selling_prices'     => [2 * (50000 + ($i * 25000))],
                'installment_prices' => [3 * (50000 + ($i * 25000))],
            ])->all(),
        ]);

        // Pay the full amount via the service.
        $paymentService->create($purchase1, [
            'amount' => $purchase1->net_amount,
        ]);

        // ------ Purchase 2: received & partially paid ------
        // Use a product NOT already stocked by purchase 1, so its selling
        // price isn't constrained by existing batch purchase prices.
        $p2Product = Product::whereNotIn('id', $products->pluck('id'))->first();

        if (! $p2Product) {
            return;
        }

        $purchase2 = $purchaseService->create([
            'supplier_id' => $supplier->id,
            'branch_id'   => $inventory->branch_id,
            'note'        => 'Second purchase order',
            'items' => [[
                'product_id' => $p2Product->id,
                'quantity'   => 10,
                'price'      => 20000,
            ]],
        ]);

        $purchaseService->receive($purchase2, [
            'inventory_id' => $inventory->id,
            'items' => [[
                'product_id'         => $p2Product->id,
                'selling_prices'     => [40000],
                'installment_prices' => [60000],
            ]],
        ]);

        // Partial payment via the service.
        $paymentService->create($purchase2, [
            'amount' => 100000,
        ]);

        // ------ Purchase Return for Purchase 2 ------
        $p2Item = $purchase2->items()->where('product_id', $p2Product->id)->first();

        $return = $returnService->create($purchase2, [
            'note' => 'Defective item returned',
            'items' => [[
                'purchase_item_id' => $p2Item->id,
                'quantity'         => 2,
                'reason'           => 'Defective product',
            ]],
        ]);

        $returnService->approve($return);
    }
}
