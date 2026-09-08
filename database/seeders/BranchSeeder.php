<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Wilaya;
use App\Services\BranchService;
use Illuminate\Database\Seeder;
use Illuminate\Http\Request;

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
            ],
            [
                'name'      => 'Second Branch',
                'code'      => 'S',
                'shop_name' => 'Rakime Second Store',
                'address'   => '456 Second Avenue, Downtown',
                'phone'     => '+213 987 654 321',
            ],
        ];

        $accounts = Account::all();
        $wilaya   = Wilaya::where('name', 'Alger')->first();

        if (! $wilaya) {
            return;
        }

        $service = app(BranchService::class);
        $request = Request::create('', 'POST');

        foreach ($branches as $data) {
            if (Branch::where('code', $data['code'])->exists()) {
                continue;
            }

            // The service creates the branch, its inventory and wallet,
            // and syncs the attached accounts.
            $service->create([
                'wilaya_id' => $wilaya->id,
                'name'      => $data['name'],
                'code'      => $data['code'],
                'shop_name' => $data['shop_name'],
                'address'   => $data['address'],
                'phone'     => $data['phone'],
                'accounts'  => $accounts->pluck('id')->all(),
            ], $request);
        }
    }
}
