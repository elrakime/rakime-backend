<?php

namespace Database\Seeders;

use App\Models\Color;
use Illuminate\Database\Seeder;

class ColorSeeder extends Seeder
{
    public function run(): void
    {
        $colors = [
            ['name' => 'White',  'code' => '#FFFFFF'],
            ['name' => 'Black',  'code' => '#000000'],
            ['name' => 'Gray',   'code' => '#808080'],
            ['name' => 'Silver', 'code' => '#C0C0C0'],
            ['name' => 'Beige',  'code' => '#F5F5DC'],
            ['name' => 'Red',    'code' => '#FF0000'],
            ['name' => 'Blue',   'code' => '#0000FF'],
            ['name' => 'Gold',   'code' => '#FFD700'],
        ];

        foreach ($colors as $data) {
            Color::firstOrCreate(['code' => $data['code']], $data);
        }
    }
}
