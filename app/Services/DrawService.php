<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Draw;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class DrawService
{
    public function list(Request $request): LengthAwarePaginator
    {
        $query = Draw::query()
            ->whereHas('subscription.contract', fn ($query) => $query->byUserBranches());

        return QueryBuilder::for($query, $request)
            ->allowedFilters(
                AllowedFilter::callback('contract_id', function ($query, string $value) {
                    $query->whereHas('subscription', fn ($query) => $query->where('contract_id', $value));
                }),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('installment_id'),
                AllowedFilter::exact('subscription_id'),
                AllowedFilter::exact('due_date'),
                AllowedFilter::callback('created_at_from', function ($query, string $value) {
                    $query->whereDate('created_at', '>=', $value);
                }),
                AllowedFilter::callback('created_at_to', function ($query, string $value) {
                    $query->whereDate('created_at', '<=', $value);
                }),
            )
            ->allowedSorts(
                AllowedSort::field('due_date'),
                AllowedSort::field('amount'),
                AllowedSort::field('last_attempted_at'),
                AllowedSort::field('created_at'),
            )
            ->defaultSort('due_date')
            ->paginate($request->integer('per_page', 15))
            ->appends($request->query());
    }
}
