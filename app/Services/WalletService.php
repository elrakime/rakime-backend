<?php

namespace App\Services;

use App\Models\Wallet;
use App\Traits\ScopesByUserBranches;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class WalletService
{
    use ScopesByUserBranches;
    public function list(Request $request): Collection
    {
        $query = Wallet::query();

        $this->scopeByUserBranches($query, 'owner');

        return QueryBuilder::for($query, $request)
            ->with('owner')
            ->allowedFilters(
                AllowedFilter::partial('name'),
                AllowedFilter::exact('owner_type'),
                AllowedFilter::exact('owner_id'),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where('name', 'like', "%{$value}%");
                }),
            )
            ->allowedSorts(
                AllowedSort::field('name'),
                AllowedSort::field('balance'),
                AllowedSort::field('created_at'),
            )
            ->defaultSort('-created_at')
            ->get();
    }

    public function create(array $data): Wallet
    {
        $wallet = Wallet::create([
            'owner_type' => $data['owner_type'] ?? null,
            'owner_id'   => $data['owner_id'] ?? null,
            'name'       => $data['name'],
            'balance'    => $data['balance'] ?? 0,
        ]);

        return $wallet->loadMissing('owner');
    }

    public function show(Wallet $wallet): Wallet
    {
        return $wallet->loadMissing('owner');
    }

    public function update(Wallet $wallet, array $data): Wallet
    {
        $wallet->update(array_filter([
            'name' => $data['name'] ?? null,
        ], fn ($v) => $v !== null));

        return $wallet->refresh()->loadMissing('owner');
    }

    public function delete(Wallet $wallet): void
    {
        $wallet->delete();
    }
}
