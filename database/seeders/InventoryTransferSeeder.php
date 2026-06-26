<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\InventoryTransfer;
use App\Models\InventoryTransferItem;
use App\Models\Stock;
use Illuminate\Database\Seeder;

class InventoryTransferSeeder extends Seeder
{
    public function run(): void
    {
        $fromInventory = Inventory::where('name', 'Main Warehouse')->first();
        $toInventory   = Inventory::where('name', 'Second Branch Warehouse')->first();
        $stock         = Stock::where('inventory_id', $fromInventory?->id)->first();

        if (!$fromInventory || !$toInventory || !$stock) {
            return;
        }

        $transfer = InventoryTransfer::create([
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

        InventoryTransferItem::create([
            'inventory_transfer_id' => $transfer->id,
            'stock_id'              => $destStock->id,
            'quantity'              => 2,
        ]);
    }
}
