<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class RoleService
{
    public function list(): Collection
    {
        return Role::with('permissions')
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): Role
    {
        $role = Role::create([
            'name'       => $data['name'],
            'guard_name' => 'web',
        ]);

        if (!empty($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return $role->loadMissing('permissions');
    }

    public function show(Role $role): Role
    {
        return $role->loadMissing('permissions');
    }

    public function update(Role $role, array $data): Role
    {
        if (isset($data['name'])) {
            $role->update(['name' => $data['name']]);
        }

        if (array_key_exists('permissions', $data)) {
            $role->syncPermissions($data['permissions'] ?? []);
        }

        return $role->refresh()->loadMissing('permissions');
    }

    public function delete(Role $role): void
    {
        $role->delete();
    }
}
