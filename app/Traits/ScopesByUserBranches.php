<?php

namespace App\Traits;

use App\Models\Branch;

trait ScopesByUserBranches
{
    /**
     * Scope a query to only include records from branches the current user has access to.
     * Admins see all records. Others see only their assigned branches.
     */
    protected function scopeByUserBranches($query, string $branchField = null): void
    {
        $user = auth()->user();

        if (! $user || $user->hasRole(\App\Enums\Role::ADMIN->value)) {
            return;
        }

        $branchIds = $user->branches()->pluck('branch_id');

        if ($branchIds->isNotEmpty()) {

            if($branchField){
                $query->where($branchField.'_type', Branch::class)
                ->whereIn($branchField.'_id', $branchIds);
            }else{
                $query->whereIn('branch_id', $branchIds);
            }
            
        }
    }
}
