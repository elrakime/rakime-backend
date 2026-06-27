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
            Role::ADMIN->value    => Role::defaultPermissions(Role::ADMIN), 

            Role::MANAGER->value  => Role::defaultPermissions(Role::MANAGER),

            Role::EMPLOYEE->value => Role::defaultPermissions(Role::EMPLOYEE),
        ];

        foreach ($matrix as $roleName => $permissions) {
            $role = SpatieRole::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions(
                array_map(fn (Permission $p) => $p->value, $permissions)
            );
        }
    }
}
