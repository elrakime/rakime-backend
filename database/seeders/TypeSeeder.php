<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Type;
use Illuminate\Database\Seeder;

class TypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'Electronics & Electrical Appliances' => [
                'Television',
                'Laptop',
                'Smartphone',
                'Printer',
            ],
            'Refrigerators & Freezers' => [
                'Single Door Refrigerator',
                'No Frost Refrigerator',
                'Chest Freezer',
            ],
            'Washing Machines & Dryers' => [
                'Automatic Washing Machine',
                'Semi-Automatic Washing Machine',
                'Clothes Dryer',
            ],
            'Kitchen Appliances' => [
                'Gas Cooker',
                'Electric Oven',
                'Microwave',
                'Blender',
                'Coffee Machine',
            ],
            'Air Conditioning & Heating' => [
                'Air Conditioner',
                'Electric Heater',
                'Fan',
            ],
            'Audio & Video Equipment' => [
                'Sound System',
                'Camera',
                'Projector',
            ],
        ];

        foreach ($types as $categoryName => $typeNames) {
            $category = Category::firstOrCreate(['name' => $categoryName]);

            foreach ($typeNames as $typeName) {
                Type::firstOrCreate(
                    ['category_id' => $category->id, 'name' => $typeName]
                );
            }
        }
    }
}
