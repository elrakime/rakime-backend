<?php

namespace Database\Seeders;

use App\Models\Expiration;
use App\Models\Inventory;
use App\Models\Stock;
use App\Services\ExpirationService;
use Illuminate\Database\Seeder;

class ExpirationSeeder extends Seeder
{
    public function run(): void
    {
        $inventory = Inventory::whereHas('branch', fn ($q) => $q->where('code', 'M'))->first();
        $stock     = Stock::where('inventory_id', $inventory?->id)->first();

        if (! $inventory || ! $stock) {
            return;
        }

        if (Expiration::where('inventory_id', $inventory->id)->exists()) {
            return;
        }

        $service = app(ExpirationService::class);

        // The service creates the expiration and its items together.
        $expiration = $service->create([
            'inventory_id' => $inventory->id,
            'note'         => 'Products expired in storage',
            'items' => [[
                'stock_id' => $stock->id,
                'quantity' => 1,
                'reason'   => 'Passed expiration date',
            ]],
        ]);

        // Approve it — this deducts stock and records the inventory movement.
        $service->approve($expiration);
    }
}
