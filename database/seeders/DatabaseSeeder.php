<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            WilayaSeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class,
            AccountSeeder::class,
            BranchSeeder::class,
            CategorySeeder::class,
            BrandSeeder::class,
            ColorSeeder::class,
            TypeSeeder::class,
            SupplierSeeder::class,
            ClientSeeder::class,
            ProductSeeder::class,
            PurchaseSeeder::class,
            RestockSeeder::class,
            ExpirationSeeder::class,
            InventoryTransferSeeder::class,
        ]);
    }
}
