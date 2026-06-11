<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\Stock;
use App\Models\Transfer;
use App\Models\TransferItem;
use Illuminate\Database\Seeder;

class TransferSeeder extends Seeder
{
    public function run(): void
    {
        $fromInventory = Inventory::where('name', 'Main Warehouse')->first();
        $toInventory   = Inventory::where('name', 'Second Branch Warehouse')->first();
        $stock         = Stock::where('inventory_id', $fromInventory?->id)->first();

        if (! $fromInventory || ! $toInventory || ! $stock) {
            return;
        }

        $transfer = Transfer::create([
            'from_inventory_id' => $fromInventory->id,
            'to_inventory_id'   => $toInventory->id,
            'note'              => 'Stock transfer to second branch',
            'transferred_at'    => now()->subDays(1),
            'received_at'       => now(),
        ]);

        // Ensure stock exists in destination inventory
        $destStock = Stock::firstOrCreate([
            'inventory_id' => $toInventory->id,
            'product_id'   => $stock->product_id,
        ]);

        TransferItem::create([
            'transfer_id' => $transfer->id,
            'stock_id'    => $destStock->id,
            'quantity'    => 2,
        ]);
    }
}
