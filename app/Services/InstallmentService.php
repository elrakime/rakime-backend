<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class InstallmentService
{
    public function list(Request $request, Contract $contract): LengthAwarePaginator
    {
        $query = $contract->installments()->getQuery();

        return QueryBuilder::for($query, $request)
            ->with(['contract.client', 'contract.account', 'contract.branch', 'cashPayment', 'draws'])
            ->allowedFilters(
                AllowedFilter::exact('status'),
                AllowedFilter::exact('payment_method'),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where('month_number', 'like', "%{$value}%");
                }),
            )
            ->allowedSorts(
                AllowedSort::field('month_number'),
                AllowedSort::field('amount'),
                AllowedSort::field('due_date'),
                AllowedSort::field('created_at'),
            )
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 15))
            ->appends($request->query());
    }
}
