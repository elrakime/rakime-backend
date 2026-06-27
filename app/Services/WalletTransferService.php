<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\WalletTransfer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class WalletTransferService
{
    public function list(Request $request): LengthAwarePaginator
    {
        return QueryBuilder::for(WalletTransfer::class, $request)
            ->with(['fromWallet', 'toWallet', 'performedBy'])
            ->allowedFilters(
                AllowedFilter::exact('from_wallet_id'),
                AllowedFilter::exact('to_wallet_id'),
                AllowedFilter::exact('performed_by'),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where('note', 'like', "%{$value}%");
                }),
            )
            ->allowedSorts(
                AllowedSort::field('amount'),
                AllowedSort::field('created_at'),
            )
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 15))
            ->appends($request->query());
    }

    public function create(array $data): WalletTransfer
    {
        return DB::transaction(function () use ($data) {
            $fromWallet = Wallet::lockForUpdate()->findOrFail($data['from_wallet_id']);

            if ($fromWallet->balance < $data['amount']) {
                throw new \Exception(__('wallet_transfers.insufficient_balance'), 422);
            }

            $fromWallet->decrement('balance', $data['amount']);
            Wallet::where('id', $data['to_wallet_id'])->increment('balance', $data['amount']);

            return WalletTransfer::create($data);
        });
    }

    public function show(WalletTransfer $walletTransfer): WalletTransfer
    {
        return $walletTransfer->load(['fromWallet', 'toWallet', 'performedBy']);
    }

    public function delete(WalletTransfer $walletTransfer): void
    {
        $walletTransfer->delete();
    }
}
