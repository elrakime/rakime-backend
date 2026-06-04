<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'name'      => 'Algerian Electronic Distribution Company',
                'phone'     => '0550123456',
                'email'     => 'contact@alde.dz',
                'address'   => '12 Rouiba Industrial Road, Algiers',
                'is_active' => true,
            ],
            [
                'name'      => 'Nour Import Enterprise',
                'phone'     => '0661234500',
                'email'     => 'nour.import@gmail.com',
                'address'   => '55 Liberation Street, Oran',
                'is_active' => true,
            ],
            [
                'name'      => 'Samsung Algeria',
                'phone'     => '0213218765432',
                'email'     => 'algeria@samsung.com',
                'address'   => 'Said Tower, Algiers',
                'is_active' => true,
            ],
            [
                'name'      => 'LG Electronics Algeria',
                'phone'     => '0213215678901',
                'email'     => 'contact@lg-algeria.com',
                'address'   => 'Reghaia Industrial Zone, Algiers',
                'is_active' => true,
            ],
            [
                'name'      => 'Condor Electronics',
                'phone'     => '0350678901',
                'email'     => 'info@condor-electronics.dz',
                'address'   => 'Mila Industrial Zone',
                'is_active' => true,
            ],
        ];

        foreach ($suppliers as $data) {
            Supplier::firstOrCreate(['phone' => $data['phone']], $data);
        }
    }
}
