<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Models\Role as SpatieRole;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create all permissions from the enum
        foreach (Permission::cases() as $permission) {
            SpatiePermission::firstOrCreate(['name' => $permission->value, 'guard_name' => 'web']);
        }

        // Define which permissions each role has
        $matrix = [
            Role::ADMIN->value    => Permission::cases(), // All permissions, all branches

            // Manager: all permissions, scoped to their branches
            Role::MANAGER->value  => Permission::cases(),

            // Employee: limited permissions, scoped to their branches
            Role::EMPLOYEE->value => [
                Permission::VIEW_CLIENTS,
                Permission::CREATE_CLIENTS,
                Permission::VIEW_INVENTORY,
                Permission::VIEW_SALES,
                Permission::CREATE_SALES,
                Permission::VIEW_RESTOCKS,
                Permission::CREATE_RESTOCKS,
                Permission::VIEW_TREASURY,
                Permission::VIEW_PRODUCTS,
                Permission::VIEW_SUPPLIERS,
                Permission::VIEW_PURCHASES,
                Permission::VIEW_TRANSFERS,
                Permission::VIEW_CATEGORIES,
                Permission::VIEW_BRANDS,
                Permission::VIEW_COLORS,
                Permission::VIEW_TYPES,
                Permission::VIEW_REPORTS,
            ],
        ];

        foreach ($matrix as $roleName => $permissions) {
            $role = SpatieRole::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions(
                array_map(fn (Permission $p) => $p->value, $permissions)
            );
        }
    }
}
