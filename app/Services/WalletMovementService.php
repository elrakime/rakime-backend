<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Branch;
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
                AllowedFilter::callback('branch_id', function ($query, $value) {
                    $query->whereHas('wallet', function ($q) use ($value) {
                        $q->where('owner_type', Branch::class)
                          ->where('owner_id', $value);
                    });
                }),
                AllowedFilter::callback('account_id', function ($query, $value) {
                    $query->whereHas('wallet', function ($q) use ($value) {
                        $q->where('owner_type', Account::class)
                          ->where('owner_id', $value);
                    });
                }),
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
