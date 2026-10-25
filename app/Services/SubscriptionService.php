<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\InstallmentStatus;
use App\Models\Subscription;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class SubscriptionService
{
    public function list(Request $request): LengthAwarePaginator
    {
        $query = Subscription::query()
            ->whereHas('contract', fn ($query) => $query->byUserBranches());

        return QueryBuilder::for($query, $request)
            ->with([
                'contract.client',
                'contract.account',
                'contract.branch',
                'contract.installments' => fn ($query) => $query->orderBy('due_date'),
                'draws',
            ])
            ->allowedFilters(
                AllowedFilter::exact('contract_id'),
                AllowedFilter::callback('branch_id', function ($query, string $value) {
                    $query->whereHas('contract', fn ($q) => $q->where('branch_id', $value));
                }),
                AllowedFilter::callback('installments_status', function ($query, string $value) {
                    $this->applyInstallmentsStatusFilter($query, $value);
                }),
                AllowedFilter::callback('created_at_from', function ($query, string $value) {
                    $query->whereDate('created_at', '>=', $value);
                }),
                AllowedFilter::callback('created_at_to', function ($query, string $value) {
                    $query->whereDate('created_at', '<=', $value);
                }),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where(function ($query) use ($value) {
                        $query->where('reference', 'like', "%{$value}%")
                            ->orWhere('subscription_number', 'like', "%{$value}%");
                    });
                }),
            )
            ->allowedSorts(
                AllowedSort::field('reference'),
                AllowedSort::field('amount'),
                AllowedSort::field('subscription_number'),
                AllowedSort::field('created_at'),
            )
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 15))
            ->appends($request->query());
    }

    /**
     * Filter subscriptions by whether the contract's installments are settled.
     *
     * - all_paid   : the contract has installments and every one of them is paid
     * - has_unpaid : the contract has at least one installment that is not paid,
     *                or has no installments at all
     */
    private function applyInstallmentsStatusFilter(Builder $query, string $value): void
    {
        match ($value) {
            'all_paid' => $query
                ->whereHas('contract.installments', function ($q) {
                    $q->where('status', InstallmentStatus::PAID);
                })
                ->whereDoesntHave('contract.installments', function ($q) {
                    $q->where('status', '!=', InstallmentStatus::PAID);
                }),

            'has_unpaid' => $query->where(function ($q) {
                $q->whereHas('contract.installments', function ($q) {
                    $q->where('status', '!=', InstallmentStatus::PAID);
                })->orWhereDoesntHave('contract.installments');
            }),

            default => null,
        };
    }
}
