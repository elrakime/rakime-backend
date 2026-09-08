<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\InventoryTransfer;
use App\Models\Stock;
use App\Services\InventoryTransferService;
use Illuminate\Database\Seeder;

class InventoryTransferSeeder extends Seeder
{
    public function run(): void
    {
        $fromInventory = Inventory::whereHas('branch', fn ($q) => $q->where('code', 'M'))->first();
        $toInventory   = Inventory::whereHas('branch', fn ($q) => $q->where('code', 'S'))->first();
        $stock         = Stock::where('inventory_id', $fromInventory?->id)->first();

        if (! $fromInventory || ! $toInventory || ! $stock) {
            return;
        }

        if (InventoryTransfer::where('from_inventory_id', $fromInventory->id)->exists()) {
            return;
        }

        $service = app(InventoryTransferService::class);

        // The service creates the transfer and its items together.
        $transfer = $service->create([
            'from_inventory_id' => $fromInventory->id,
            'to_inventory_id'   => $toInventory->id,
            'note'              => 'Stock transfer to second branch',
            'items' => [[
                'stock_id' => $stock->id,
                'quantity' => 2,
            ]],
        ]);

        // Dispatch and receive — this moves stock and records movements.
        $service->dispatch($transfer);
        $service->receive($transfer);
    }
}
