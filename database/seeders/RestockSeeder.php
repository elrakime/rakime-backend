<?php

namespace Database\Seeders;

use App\Enums\RestockStatus;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Restock;
use App\Models\RestockItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class RestockSeeder extends Seeder
{
    public function run(): void
    {
        $user    = User::where('email', 'admin@example.com')->first();
        $branch  = Branch::where('code', 'M')->first();
        $product = Product::first();

        if (! $user || ! $branch || ! $product) {
            return;
        }

        $restock = Restock::create([
            'user_id'      => $user->id,
            'branch_id'    => $branch->id,
            'reference'    => 'RST-2024-001',
            'status'       => RestockStatus::SUBMITTED,
            'note'         => 'Restock request for low inventory',
            'fulfilled_at' => null,
        ]);

        $item = new RestockItem();
        $item->forceFill([
            'restock_id'          => $restock->id,
            'product_id'          => $product->id,
            'requested_quantity'  => 10,
            'fulfilled_quantity'  => 0,
        ])->save();
    }
}
