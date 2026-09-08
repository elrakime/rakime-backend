<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Restock;
use App\Services\RestockService;
use Illuminate\Database\Seeder;

class RestockSeeder extends Seeder
{
    public function run(): void
    {
        $branch  = Branch::where('code', 'M')->first();
        $product = Product::first();

        if (! $branch || ! $product) {
            return;
        }

        if (Restock::where('branch_id', $branch->id)->exists()) {
            return;
        }

        // The service creates the restock and its items together.
        app(RestockService::class)->create([
            'branch_id' => $branch->id,
            'note'      => 'Restock request for low inventory',
            'items' => [[
                'product_id'         => $product->id,
                'requested_quantity' => 10,
            ]],
        ]);
    }
}
