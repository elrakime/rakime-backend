<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Color;
use App\Models\Product;
use App\Models\Type;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'type'         => 'No Frost Refrigerator',
                'brand'        => 'Samsung',
                'color'        => '#C0C0C0',
                'name'         => 'Samsung RT38 No Frost Refrigerator 360L',
                'barcode'      => '8801643033569',
                'min_quantity' => 3,
            ],
            [
                'type'         => 'No Frost Refrigerator',
                'brand'        => 'LG',
                'color'        => '#C0C0C0',
                'name'         => 'LG GN-B392 No Frost Refrigerator 393L',
                'barcode'      => '6935280150168',
                'min_quantity' => 3,
            ],
            [
                'type'         => 'Automatic Washing Machine',
                'brand'        => 'Samsung',
                'color'        => '#FFFFFF',
                'name'         => 'Samsung WW70J5355MW Washing Machine 7kg',
                'barcode'      => '8806088100463',
                'min_quantity' => 2,
            ],
            [
                'type'         => 'Air Conditioner',
                'brand'        => 'Condor',
                'color'        => '#FFFFFF',
                'name'         => 'Condor CS-12 Air Conditioner 12000 BTU',
                'barcode'      => '6291001080072',
                'min_quantity' => 5,
            ],
            [
                'type'         => 'Television',
                'brand'        => 'Samsung',
                'color'        => '#000000',
                'name'         => 'Samsung UE55TU7000 55 inch 4K Television',
                'barcode'      => '8806090361753',
                'min_quantity' => 2,
            ],
            [
                'type'         => 'Air Conditioner',
                'brand'        => 'LG',
                'color'        => '#FFFFFF',
                'name'         => 'LG S12ET Air Conditioner 12000 BTU',
                'barcode'      => '6935280172312',
                'min_quantity' => 5,
            ],
        ];

        foreach ($products as $data) {
            $type  = Type::where('name', $data['type'])->first();
            $brand = Brand::where('name', $data['brand'])->first();
            $color = Color::where('code', $data['color'])->first();

            if (! $type || ! $brand || ! $color) {
                continue;
            }

            Product::firstOrCreate(
                ['barcode' => $data['barcode']],
                [
                    'type_id'      => $type->id,
                    'brand_id'     => $brand->id,
                    'color_id'     => $color->id,
                    'name'         => $data['name'],
                    'barcode'      => $data['barcode'],
                    'min_quantity' => $data['min_quantity'],
                ]
            );
        }
    }
}
