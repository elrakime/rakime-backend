<?php

namespace Database\Seeders;

use App\Models\Supplier;
use App\Models\Wilaya;
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
                'wilaya'    => 'Alger',
                'is_active' => true,
            ],
            [
                'name'      => 'Nour Import Enterprise',
                'phone'     => '0661234500',
                'email'     => 'nour.import@gmail.com',
                'address'   => '55 Liberation Street, Oran',
                'wilaya'    => 'Oran',
                'is_active' => true,
            ],
            [
                'name'      => 'Samsung Algeria',
                'phone'     => '0213218765432',
                'email'     => 'algeria@samsung.com',
                'address'   => 'Said Tower, Algiers',
                'wilaya'    => 'Alger',
                'is_active' => true,
            ],
            [
                'name'      => 'LG Electronics Algeria',
                'phone'     => '0213215678901',
                'email'     => 'contact@lg-algeria.com',
                'address'   => 'Reghaia Industrial Zone, Algiers',
                'wilaya'    => 'Alger',
                'is_active' => true,
            ],
            [
                'name'      => 'Condor Electronics',
                'phone'     => '0350678901',
                'email'     => 'info@condor-electronics.dz',
                'address'   => 'Mila Industrial Zone',
                'wilaya'    => 'Mila',
                'is_active' => true,
            ],
        ];

        $fallbackWilaya = Wilaya::where('name', 'Alger')->first();

        if (! $fallbackWilaya) {
            return;
        }

        foreach ($suppliers as $data) {
            $wilayaName = $data['wilaya'];
            unset($data['wilaya']);

            $wilaya = Wilaya::where('name', $wilayaName)->first() ?? $fallbackWilaya;

            Supplier::firstOrCreate(
                ['phone' => $data['phone']],
                array_merge($data, ['wilaya_id' => $wilaya->id]),
            );
        }
    }
}
