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
        return QueryBuilder::for(WalletMovement::class)
            ->with(['performedBy', 'source', 'wallet'])
            ->allowedFilters(
                AllowedFilter::exact('wallet_id'),
                AllowedFilter::exact('movement_type'),
                AllowedFilter::scope('inflow'),
                AllowedFilter::scope('outflow'),
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
