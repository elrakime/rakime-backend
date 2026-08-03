<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class SubscriptionService
{
    public function list(Request $request, Contract $contract): LengthAwarePaginator
    {
        $query = $contract->subscriptions()->getQuery();

        return QueryBuilder::for($query, $request)
            ->with(['contract.client', 'contract.account', 'contract.branch', 'draws'])
            ->allowedFilters(
                AllowedFilter::exact('status'),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where('reference', 'like', "%{$value}%")
                        ->orWhere('subscription_number', 'like', "%{$value}%");
                }),
            )
            ->allowedSorts(
                AllowedSort::field('reference'),
                AllowedSort::field('amount'),
                AllowedSort::field('subscription_number'),
                AllowedSort::field('total_months'),
                AllowedSort::field('created_at'),
            )
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 15))
            ->appends($request->query());
    }
}
