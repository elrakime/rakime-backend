<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class AccountService
{
    public function list(Request $request): LengthAwarePaginator
    {
        return QueryBuilder::for(Account::class, $request)
            ->allowedFilters(
                AllowedFilter::partial('name'),
                AllowedFilter::partial('ccp_number'),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('name', 'like', "%{$value}%")
                          ->orWhere('ccp_number', 'like', "%{$value}%");
                    });
                }),
            )
            ->allowedSorts(
                AllowedSort::field('name'),
                AllowedSort::field('ccp_number'),
                AllowedSort::field('draw_day'),
                AllowedSort::field('created_at'),
            )
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 15))
            ->appends($request->query());
    }

    public function create(array $data): Account
    {
        $account = Account::create([
            'name'                => $data['name'],
            'ccp_number'          => $data['ccp_number'],
            'ccp_key'             => $data['ccp_key'],
            'draw_day'            => $data['draw_day'],
            'min_withdraw_amount' => $data['min_withdraw_amount'],
            'max_withdraw_count'  => $data['max_withdraw_count'],
        ]);

        Wallet::firstOrCreate(
            ['owner_type' => 'account', 'owner_id' => $account->id],
            ['name' => $account->name, 'balance' => 0],
        );

        return $account;
    }

    public function show(Account $account): Account
    {
        return $account;
    }

    public function update(Account $account, array $data): Account
    {
        $account->update(array_filter([
            'name'                => $data['name'] ?? null,
            'ccp_number'          => $data['ccp_number'] ?? null,
            'ccp_key'             => $data['ccp_key'] ?? null,
            'draw_day'            => $data['draw_day'] ?? null,
            'min_withdraw_amount' => $data['min_withdraw_amount'] ?? null,
            'max_withdraw_count'  => $data['max_withdraw_count'] ?? null,
        ], fn ($v) => $v !== null));

        return $account->refresh();
    }

    public function delete(Account $account): void
    {
        $account->delete();
    }
}
