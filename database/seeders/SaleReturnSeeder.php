<?php

namespace Database\Seeders;

use App\Models\Sale;
use App\Models\SaleReturn;
use App\Services\SaleReturnService;
use Illuminate\Database\Seeder;

class SaleReturnSeeder extends Seeder
{
    public function run(): void
    {
        $sale = Sale::with('items')->first();

        if (! $sale || $sale->items->isEmpty()) {
            return;
        }

        if (SaleReturn::where('sale_id', $sale->id)->exists()) {
            return;
        }

        $service = app(SaleReturnService::class);

        // Return a single item from the sale.
        $saleItem = $sale->items->first();

        $saleReturn = $service->create($sale, [
            'note' => 'Seeded sale return',
            'items' => [[
                'sale_item_id' => $saleItem->id,
                'quantity'     => 1,
                'reason'       => 'Customer returned item',
            ]],
        ]);

        // Approve it — this credits stock back and debits the wallet.
        $service->approve($saleReturn);
    }
}
