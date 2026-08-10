<?php

namespace Database\Seeders;

use App\Enums\PurchaseStatus;
use App\Models\Batch;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchasePayment;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Stock;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class PurchaseSeeder extends Seeder
{
    public function run(): void
    {
        $supplier  = Supplier::first();
        $inventory = Inventory::where('name', 'Main Warehouse')->first();
        $products  = Product::take(3)->get();

        if (! $supplier || ! $inventory || $products->isEmpty()) {
            return;
        }

        // ------ Purchase 1: completed & fully paid ------
        $purchase1 = Purchase::create([
            'supplier_id'  => $supplier->id,
            'reference'    => 'PO-2024-001',
            'status'       => PurchaseStatus::COMPLETED,
            'total_amount' => 150000,
            'paid_amount'  => 150000,
            'note'         => 'First purchase order',
        ]);

        foreach ($products as $i => $product) {
            $price    = 50000 + ($i * 25000);
            $quantity = 5 + $i;

            $item = PurchaseItem::create([
                'purchase_id' => $purchase1->id,
                'product_id'  => $product->id,
                'quantity'    => $quantity,
                'price'       => $price,
            ]);

            $stock = Stock::firstOrCreate([
                'inventory_id' => $inventory->id,
                'product_id'   => $product->id,
            ]);

            Batch::firstOrCreate(
                [
                    'stock_id'    => $stock->id,
                    'source_id'   => $item->id,
                    'source_type' => PurchaseItem::class,
                ],
                [
                    'purchase_price'   => $price,
                    'initial_quantity'  => $quantity,
                    'current_quantity'  => $quantity,
                ]
            );
        }

        // Full bank payment
        PurchasePayment::create([
            'purchase_id' => $purchase1->id,
            'amount'      => 150000,
        ]);

        // ------ Purchase 2: completed & partially paid ------
        $purchase2 = Purchase::create([
            'supplier_id'  => $supplier->id,
            'reference'    => 'PO-2024-002',
            'status'       => PurchaseStatus::COMPLETED,
            'total_amount' => 200000,
            'paid_amount'  => 100000,
            'note'         => 'Second purchase order',
        ]);

        $p2Product = $products->last();

        $p2Item = PurchaseItem::create([
            'purchase_id' => $purchase2->id,
            'product_id'  => $p2Product->id,
            'quantity'    => 10,
            'price'       => 20000,
        ]);

        $stock2 = Stock::firstOrCreate([
            'inventory_id' => $inventory->id,
            'product_id'   => $p2Product->id,
        ]);

        Batch::firstOrCreate(
            [
                'stock_id'    => $stock2->id,
                'source_id'   => $p2Item->id,
                'source_type' => PurchaseItem::class,
            ],
            [
                'purchase_price'   => 20000,
                'initial_quantity'  => 10,
                'current_quantity'  => 10,
            ]
        );

        // Partial cash payment
        PurchasePayment::create([
            'purchase_id' => $purchase2->id,
            'amount'      => 100000,
        ]);

        // ------ Purchase Return for Purchase 2 ------
        $return = PurchaseReturn::create([
            'purchase_id' => $purchase2->id,
            'reference'   => 'PR-2024-001',
            'note'        => 'Defective item returned',
        ]);

        PurchaseReturnItem::create([
            'purchase_return_id' => $return->id,
            'purchase_item_id'   => $p2Item->id,
            'quantity'           => 2,
            'reason'             => 'Defective product',
        ]);
    }
}
