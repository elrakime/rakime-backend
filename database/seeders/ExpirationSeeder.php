<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Expiration;
use App\Models\ExpirationItem;
use App\Models\Inventory;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExpirationSeeder extends Seeder
{
    public function run(): void
    {
        $user      = User::where('email', 'admin@example.com')->first();
        $inventory = Inventory::where('name', 'Main Warehouse')->first();
        $stock     = Stock::with('batches')->first();

        if (! $user || ! $inventory || ! $stock) {
            return;
        }

        $batch = $stock->batches->first();
        if (! $batch) {
            return;
        }

        $expiration = Expiration::create([
            'user_id'      => $user->id,
            'inventory_id' => $inventory->id,
            'reference'    => 'EXP-2024-001',
            'note'         => 'Products expired in storage',
            'reported_at'  => now()->subDays(1),
            'approved_at'  => now(),
        ]);

        ExpirationItem::create([
            'expiration_id' => $expiration->id,
            'stock_id'      => $stock->id,
            'batch_id'      => $batch->id,
            'quantity'      => 1,
            'reason'        => 'Passed expiration date',
        ]);
    }
}
