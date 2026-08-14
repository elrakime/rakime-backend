<?php

namespace App\Services;

use App\Enums\Role as RoleEnum;
use Exception;
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
        $this->assertNotDefault($role, __('roles.cannot_update_default'));

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
        $this->assertNotDefault($role, __('roles.cannot_delete_default'));

        if ($role->users()->exists()) {
            throw new Exception(__('roles.cannot_delete_role_with_users'), 422);
        }

        $role->delete();
    }

    private function assertNotDefault(Role $role, string $message): void
    {
        if (in_array($role->name, RoleEnum::keys(), true)) {
            throw new Exception($message, 422);
        }
    }
}
