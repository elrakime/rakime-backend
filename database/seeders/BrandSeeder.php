<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            'Samsung',
            'LG',
            'Condor',
            'Brandt',
            'Iris',
            'Beko',
            'Bosch',
            'Whirlpool',
            'Hisense',
            'Haier',
        ];

        foreach ($brands as $name) {
            Brand::firstOrCreate(['name' => $name]);
        }
    }
}
