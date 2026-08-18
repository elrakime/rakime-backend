<?php

namespace App\Services;

use App\Models\WalletMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class WalletMovementService
{
    public function list(): LengthAwarePaginator
    {
        $query = WalletMovement::query();

        $query->byUserBranches();

        return QueryBuilder::for($query)
            ->with(['source', 'wallet'])
            ->allowedFilters(
                AllowedFilter::exact('wallet_id'),
                AllowedFilter::exact('movement_type'),
                AllowedFilter::scope('inflow'),
                AllowedFilter::scope('outflow'),
                AllowedFilter::callback('from_date', function ($query, string $value) {
                    $query->whereDate('created_at', '>=', $value);
                }),
                AllowedFilter::callback('to_date', function ($query, string $value) {
                    $query->whereDate('created_at', '<=', $value);
                }),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where('note', 'like', "%{$value}%");
                }),
            )
            ->allowedSorts(
                AllowedSort::field('amount'),
                AllowedSort::field('created_at'),
            )
            ->defaultSort('-created_at')
            ->paginate(request()->integer('per_page', 15));
    }
}
