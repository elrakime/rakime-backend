<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Treasury;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            [
                'name' => 'Main Branch',
                'code' => 'M',
                'inventory' => 'Main Warehouse',
                'treasury'  => 'Main Treasury',
            ],
            [
                'name' => 'Second Branch',
                'code' => 'S',
                'inventory' => 'Second Branch Warehouse',
                'treasury'  => 'Second Branch Treasury',
            ],
        ];

        $accounts = Account::all();

        foreach ($branches as $data) {
            $branch = Branch::firstOrCreate(
                ['code' => $data['code']],
                ['name' => $data['name']]
            );

            // Attach all accounts to this branch
            foreach ($accounts as $account) {
                $branch->accounts()->syncWithoutDetaching([$account->id]);
            }

            // Create inventory for the branch
            Inventory::firstOrCreate(
                ['branch_id' => $branch->id, 'name' => $data['inventory']],
            );

            // Create treasury for the branch
            Treasury::firstOrCreate(
                ['branch_id' => $branch->id, 'name' => $data['treasury']],
                ['balance' => 0]
            );
        }
    }
}
