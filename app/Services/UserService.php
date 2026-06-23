<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class UserService
{
    public function list(Request $request): LengthAwarePaginator
    {
        return QueryBuilder::for(User::class, $request)
            ->with(['roles', 'branches'])
            ->allowedFilters(
                AllowedFilter::partial('name'),
                AllowedFilter::partial('email'),
                AllowedFilter::partial('phone'),
                AllowedFilter::exact('is_active'),
                AllowedFilter::callback('search', function (Builder $query, string $value) {
                    $query->where(function (Builder $q) use ($value) {
                        $q->where('name', 'like', "%{$value}%")
                          ->orWhere('email', 'like', "%{$value}%")
                          ->orWhere('phone', 'like', "%{$value}%");
                    });
                }),
            )
            ->allowedSorts(
                AllowedSort::field('name'),
                AllowedSort::field('email'),
                AllowedSort::field('is_active'),
                AllowedSort::field('created_at'),
            )
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 15))
            ->appends($request->query());
    }

    public function create(array $data): User
    {
        $roles    = $data['roles'] ?? [];
        $branches = $data['branches'] ?? [];

        // Admin should not be linked to specific branches
        if (in_array(\App\Enums\Role::ADMIN->value, $roles)) {
            $branches = [];
        }

        // Non-admin users must be linked to at least one branch
        if (! in_array(\App\Enums\Role::ADMIN->value, $roles) && empty($branches)) {
            throw new \InvalidArgumentException('Non-admin users must be assigned to at least one branch.', 422);
        }

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => $data['password'],
            'phone'     => $data['phone'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        if ($roles) {
            $user->syncRoles($roles);
        }

        if ($branches) {
            $user->branches()->sync($branches);
        }

        return $user->loadMissing(['roles', 'branches']);
    }

    public function show(User $user): User
    {
        return $user->loadMissing(['roles', 'permissions', 'branches']);
    }

    public function update(User $user, array $data): User
    {
        $roles    = $data['roles'] ?? null;
        $branches = $data['branches'] ?? null;

        // Determine effective roles for branch validation
        $effectiveRoles = $roles ?? $user->roles->pluck('name')->toArray();

        // Admin should not be linked to specific branches
        if (in_array(\App\Enums\Role::ADMIN->value, $effectiveRoles)) {
            $branches = [];
        }

        // Non-admin users must be linked to at least one branch
        if (! in_array(\App\Enums\Role::ADMIN->value, $effectiveRoles) && is_array($branches) && empty($branches)) {
            throw new \InvalidArgumentException('Non-admin users must be assigned to at least one branch.', 422);
        }

        $user->update(array_filter([
            'name'      => $data['name'] ?? null,
            'email'     => $data['email'] ?? null,
            'phone'     => $data['phone'] ?? null,
            'is_active' => $data['is_active'] ?? null,
        ], fn ($v) => $v !== null));

        if (isset($data['password'])) {
            $user->update(['password' => $data['password']]);
        }

        if ($roles !== null) {
            $user->syncRoles($roles);
        }

        if ($branches !== null) {
            $user->branches()->sync($branches);
        }

        return $user->loadMissing(['roles', 'permissions', 'branches']);
    }

    public function delete(User $user): void
    {
        $user->delete();
    }
}
