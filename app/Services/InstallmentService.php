<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Installment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class InstallmentService
{
    public function list(Request $request): LengthAwarePaginator
    {
        $query = Installment::query()
            ->whereHas('contract', fn ($query) => $query->byUserBranches());

        return QueryBuilder::for($query, $request)
            ->with(['contract.client', 'contract.account', 'contract.branch', 'payments', 'draws'])
            ->allowedFilters(
                AllowedFilter::exact('contract_id'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('payment_method'),
                AllowedFilter::callback('created_at_from', function ($query, string $value) {
                    $query->whereDate('created_at', '>=', $value);
                }),
                AllowedFilter::callback('created_at_to', function ($query, string $value) {
                    $query->whereDate('created_at', '<=', $value);
                }),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where('due_date', 'like', "%{$value}%");
                }),
            )
            ->allowedSorts(
                AllowedSort::field('due_date'),
                AllowedSort::field('amount'),
                AllowedSort::field('created_at'),
            )
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 15))
            ->appends($request->query());
    }
}
