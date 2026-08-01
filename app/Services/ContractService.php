<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Traits\ScopesByUserBranches;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class ContractService
{
    use ScopesByUserBranches;

    public function list(Request $request): LengthAwarePaginator
    {
        $query = Contract::query();

        $this->scopeByUserBranches($query);

        return QueryBuilder::for($query, $request)
            ->with(['client', 'account', 'branch', 'items.product', 'items.stock'])
            ->allowedFilters(
                AllowedFilter::exact('branch_id'),
                AllowedFilter::exact('client_id'),
                AllowedFilter::exact('status'),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('reference', 'like', "%{$value}%")
                          ->orWhere('note', 'like', "%{$value}%");
                    });
                }),
            )
            ->allowedSorts(
                AllowedSort::field('reference'),
                AllowedSort::field('total_amount'),
                AllowedSort::field('created_at'),
            )
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 15))
            ->appends($request->query());
    }

    public function create(array $data): Contract
    {
        $contract = Contract::create([
            'client_id'  => $data['client_id'],
            'account_id' => $data['account_id'],
            'branch_id'  => $data['branch_id'],
            'status'     => ContractStatus::PENDING,
            'note'       => $data['note'] ?? null,
        ]);

        return $contract->load(['client', 'account', 'branch']);
    }
}
