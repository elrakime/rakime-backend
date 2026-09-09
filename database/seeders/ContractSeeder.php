<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Product;
use App\Models\Stock;
use App\Services\ContractService;
use Illuminate\Database\Seeder;

class ContractSeeder extends Seeder
{
    public function run(): void
    {
        $branch  = Branch::where('code', 'M')->first();
        $client  = Client::first();
        $account = Account::first();
        $product = Product::first();

        if (! $branch || ! $client || ! $account || ! $product) {
            return;
        }

        if (Contract::where('branch_id', $branch->id)->exists()) {
            return;
        }

        // A stock in the branch inventory to attach to the contract item.
        $stock = Stock::whereHas('inventory', fn ($q) => $q->where('branch_id', $branch->id))
            ->where('product_id', $product->id)
            ->first();

        if (! $stock) {
            return;
        }

        $service = app(ContractService::class);

        // Amounts: 3 items × 100,000 = 300,000 total.
        // Advance 60,000 → net 240,000 over 12 months → monthly 20,000.
        $contract = $service->create([
            'client_id'      => $client->id,
            'account_id'     => $account->id,
            'branch_id'      => $branch->id,
            'advance_amount' => 60000,
            'months_count'   => 12,
            'note'           => 'Seeded installment contract',
            'items' => [[
                'product_id' => $product->id,
                'stock_id'   => $stock->id,
                'quantity'   => 3,
                'price'      => 100000,
            ]],
        ]);

        // Approve with a max_amount above the net amount.
        $service->approve($contract, 300000);

        // Configure: 2 subscriptions (≤ max_withdraw_count), draw day matching
        // the account's draw_day (15). Contract starts on 15-06-2026.
        $service->configure($contract, 2, '2026-06-15');
    }
}
