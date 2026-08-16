<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contract;
use App\Models\Draw;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class DrawService
{
    public function list(Request $request, Contract $contract): LengthAwarePaginator
    {
        $query = Draw::whereHas('subscription', function ($query) use ($contract) {
            $query->where('contract_id', $contract->id);
        });

        return QueryBuilder::for($query, $request)
            ->allowedFilters(
                AllowedFilter::exact('status'),
                AllowedFilter::exact('installment_id'),
                AllowedFilter::exact('subscription_id'),
                AllowedFilter::exact('month_number'),
            )
            ->allowedSorts(
                AllowedSort::field('month_number'),
                AllowedSort::field('amount'),
                AllowedSort::field('scheduled_date'),
                AllowedSort::field('created_at'),
            )
            ->defaultSort('scheduled_date')
            ->paginate($request->integer('per_page', 15))
            ->appends($request->query());
    }
}
