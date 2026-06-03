<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;

class PermissionService
{
    public function list(): Collection
    {
        return Permission::where('guard_name', 'web')
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Permission $p) => explode('.', $p->name)[0])
            ->map(fn (Collection $permissions, string $group) => [
                'group'       => $group,
                'permissions' => $permissions->map(fn (Permission $p) => [
                    'id'   => $p->id,
                    'name' => $p->name,
                ])->values(),
            ])
            ->values();
    }
}
