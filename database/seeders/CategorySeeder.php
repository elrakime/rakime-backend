<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Electronics & Electrical Appliances',
            'Refrigerators & Freezers',
            'Washing Machines & Dryers',
            'Kitchen Appliances',
            'Air Conditioning & Heating',
            'Audio & Video Equipment',
        ];

        foreach ($categories as $name) {
            Category::firstOrCreate(['name' => $name]);
        }
    }
}
