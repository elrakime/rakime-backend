<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Wilaya;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::where('code', 'M')->first();
        $wilaya = Wilaya::where('name', 'Alger')->first();

        if (! $branch || ! $wilaya) {
            return;
        }

        $clients = [
            [
                'firstname'   => 'Ahmed',
                'lastname'    => 'Benali',
                'phone'       => '0555123456',
                'nin'         => '198501612345678',
                'ccp_number'  => '1234567890',
                'ccp_key'     => '12',
                'birthdate'   => '1985-06-15',
                'address'     => "12 Rue de l'Independence, Alger",
                'occupation'  => 'Engineer',
                'employer'    => 'Sonatrach',
                'salary'      => 120000,
                'eccp'        => null,
            ],
            [
                'firstname'   => 'Fatima Zahra',
                'lastname'    => 'Boualem',
                'phone'       => '0661234567',
                'nin'         => '199002161234567',
                'ccp_number'  => '9876543210',
                'ccp_key'     => '34',
                'birthdate'   => '1990-02-20',
                'address'     => 'Cite El Salam, Alger',
                'occupation'  => 'Teacher',
                'employer'    => 'Ministry of Education',
                'salary'      => 80000,
                'eccp'        => null,
            ],
            [
                'firstname'   => 'Mohamed Amine',
                'lastname'    => 'Karimi',
                'phone'       => '0771234567',
                'nin'         => '198801634567890',
                'ccp_number'  => '1122334455',
                'ccp_key'     => '56',
                'birthdate'   => '1988-07-10',
                'address'     => 'Cite El Amel, Alger',
                'occupation'  => 'Doctor',
                'employer'    => 'Public Hospital',
                'salary'      => 200000,
                'eccp'        => null,
            ],
        ];

        foreach ($clients as $data) {
            Client::firstOrCreate(
                ['nin' => $data['nin']],
                array_merge($data, [
                    'branch_id' => $branch->id,
                    'wilaya_id' => $wilaya->id,
                ])
            );
        }
    }
}
