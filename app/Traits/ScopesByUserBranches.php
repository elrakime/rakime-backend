<?php

namespace App\Traits;

trait ScopesByUserBranches
{
    /**
     * Scope a query to only include records from branches the current user has access to.
     * Admins see all records. Others see only their assigned branches.
     */
    protected function scopeByUserBranches($query, string $branchField = 'branch_id'): void
    {
        $user = auth()->user();

        if (! $user || $user->hasRole(\App\Enums\Role::ADMIN->value)) {
            return;
        }

        $branchIds = $user->branches()->pluck('branch_id');

        if ($branchIds->isNotEmpty()) {
            $query->whereIn($branchField, $branchIds);
        }
    }
}
