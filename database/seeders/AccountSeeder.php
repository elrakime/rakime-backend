<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'name'                => 'Main CCP Account',
                'ccp_number'          => '0012345678',
                'ccp_key'             => '42',
                'draw_day'            => 15,
                'min_withdraw_amount' => 5000,
                'max_withdraw_count'  => 3,
            ],
            [
                'name'                => 'Secondary CCP Account',
                'ccp_number'          => '0098765432',
                'ccp_key'             => '17',
                'draw_day'            => 1,
                'min_withdraw_amount' => 10000,
                'max_withdraw_count'  => 5,
            ],
        ];

        foreach ($accounts as $data) {
            Account::firstOrCreate(['ccp_number' => $data['ccp_number']], $data);
        }
    }
}
