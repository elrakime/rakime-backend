<?php

namespace App\Services;

use App\Enums\WalletMovementType;
use App\Models\Wallet;
use App\Models\WalletMovement;
use App\Traits\ScopesByUserBranches;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

    public function deposit(Wallet $wallet, array $data): Wallet
    {
        return DB::transaction(function () use ($wallet, $data) {
            $wallet->increment('balance', $data['amount']);

            WalletMovement::create([
                'wallet_id'      => $wallet->id,
                'movement_type'  => WalletMovementType::DEPOSIT,
                'amount'         => $data['amount'],
                'note'           => $data['note'] ?? null,
                'performed_by'   => $data['performed_by'] ?? null,
            ]);

            return $wallet->refresh()->loadMissing('owner');
        });
    }

    public function withdraw(Wallet $wallet, array $data): Wallet
    {
        return DB::transaction(function () use ($wallet, $data) {
            if ($wallet->balance < $data['amount']) {
                throw new \Exception(__('wallet_transfers.insufficient_balance'), 422);
            }

            $wallet->decrement('balance', $data['amount']);

            WalletMovement::create([
                'wallet_id'      => $wallet->id,
                'movement_type'  => WalletMovementType::WITHDRAWAL,
                'amount'         => -$data['amount'],
                'note'           => $data['note'] ?? null,
                'performed_by'   => $data['performed_by'] ?? null,
            ]);

            return $wallet->refresh()->loadMissing('owner');
        });
    }
}
