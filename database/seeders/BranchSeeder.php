<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            [
                'name'      => 'Main Branch',
                'code'      => 'M',
                'shop_name' => 'Rakime Main Store',
                'address'   => '123 Main Street, City Center',
                'phone'     => '+213 123 456 789',
                'inventory' => 'Main Warehouse',
                'wallet'    => 'Main Wallet',
            ],
            [
                'name'      => 'Second Branch',
                'code'      => 'S',
                'shop_name' => 'Rakime Second Store',
                'address'   => '456 Second Avenue, Downtown',
                'phone'     => '+213 987 654 321',
                'inventory' => 'Second Branch Warehouse',
                'wallet'    => 'Second Branch Wallet',
            ],
        ];

        $accounts = Account::all();

        foreach ($branches as $data) {
            $branch = Branch::firstOrCreate(
                ['code' => $data['code']],
                [
                    'name'      => $data['name'],
                    'shop_name' => $data['shop_name'],
                    'address'   => $data['address'],
                    'phone'     => $data['phone'],
                ]
            );

            // Attach all accounts to this branch
            foreach ($accounts as $account) {
                $branch->accounts()->syncWithoutDetaching([$account->id]);
            }

            // Create inventory for the branch
            Inventory::firstOrCreate(
                ['branch_id' => $branch->id, 'name' => $data['inventory']],
            );

            // Create wallet for the branch
            Wallet::firstOrCreate(
                ['owner_type' => Branch::class, 'owner_id' => $branch->id, 'name' => $data['wallet']],
                ['balance' => 0]
            );
        }
    }
}
