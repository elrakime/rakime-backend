<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Inventory;
use App\Models\Sale;
use App\Models\Stock;
use App\Services\SaleService;
use Illuminate\Database\Seeder;

class SaleSeeder extends Seeder
{
    public function run(): void
    {
        $branch    = Branch::where('code', 'M')->first();
        $client    = Client::first();
        $inventory = Inventory::whereHas('branch', fn ($q) => $q->where('code', 'M'))->first();

        if (! $branch || ! $client || ! $inventory) {
            return;
        }

        if (Sale::where('branch_id', $branch->id)->exists()) {
            return;
        }

        // Pick stocks in the branch inventory that actually have quantity on hand.
        $stocks = Stock::where('inventory_id', $inventory->id)
            ->withSum('batches as total_current', 'current_quantity')
            ->having('total_current', '>', 0)
            ->orderBy('id')
            ->take(2)
            ->get();

        if ($stocks->isEmpty()) {
            return;
        }

        // Build sale items with realistic quantities (not exceeding stock).
        $items = $stocks->map(fn ($stock) => [
            'stock_id' => $stock->id,
            'quantity' => 1,
        ])->all();

        // The service resolves prices from each stock's selling price,
        // validates against purchase cost, deducts stock and credits the wallet.
        app(SaleService::class)->create([
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'note'      => 'Seeded sale',
            'items'     => $items,
        ]);
    }
}
