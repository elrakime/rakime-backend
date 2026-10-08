<?php

declare(strict_types=1);

namespace App\Traits;

use Carbon\Carbon;

trait HasStatsFilters
{
    /**
     * Apply an optional branch filter to a query.
     */
    protected function applyBranch($query, string $column, ?int $branchId): void
    {
        if ($branchId !== null) {
            $query->where($column, $branchId);
        }
    }

    /**
     * Apply an optional account filter to a query.
     */
    protected function applyAccount($query, string $column, ?int $accountId): void
    {
        if ($accountId !== null) {
            $query->where($column, $accountId);
        }
    }

    /**
     * Apply an optional date range to a query on the given column.
     */
    protected function applyDateRange($query, ?Carbon $start, ?Carbon $end, string $column = 'created_at'): void
    {
        if ($start !== null) {
            $query->where($column, '>=', $start);
        }

        if ($end !== null) {
            $query->where($column, '<=', $end);
        }
    }
}
