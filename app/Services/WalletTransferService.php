<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Branch;
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
    public function __construct(private readonly WalletService $walletService) {}

    public function list(Request $request): LengthAwarePaginator
    {
        $query = WalletTransfer::query();

        $query->byUserBranches();

        return QueryBuilder::for($query, $request)
            ->with(['fromWallet', 'toWallet'])
            ->allowedFilters(
                AllowedFilter::exact('from_wallet_id'),
                AllowedFilter::exact('to_wallet_id'),
                AllowedFilter::callback('branch_id', function ($query, $value) {
                    $query->where(function ($q) use ($value) {
                        $walletBranch = fn ($sub) => $sub->where('owner_type', Branch::class)
                            ->where('owner_id', $value);
                        $q->whereHas('fromWallet', $walletBranch)
                          ->orWhereHas('toWallet', $walletBranch);
                    });
                }),
                AllowedFilter::callback('account_id', function ($query, $value) {
                    $query->where(function ($q) use ($value) {
                        $walletAccount = fn ($sub) => $sub->where('owner_type', Account::class)
                            ->where('owner_id', $value);
                        $q->whereHas('fromWallet', $walletAccount)
                          ->orWhereHas('toWallet', $walletAccount);
                    });
                }),
                AllowedFilter::callback('created_at_from', function ($query, string $value) {
                    $query->whereDate('created_at', '>=', $value);
                }),
                AllowedFilter::callback('created_at_to', function ($query, string $value) {
                    $query->whereDate('created_at', '<=', $value);
                }),
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
            $toWallet   = Wallet::findOrFail($data['to_wallet_id']);

            $transfer = WalletTransfer::create($data);

            $this->walletService->transferOut(
                wallet: $fromWallet,
                amount: $transfer->amount,
                source: $transfer,
                note: $transfer->note,
            );

            $this->walletService->transferIn(
                wallet: $toWallet,
                amount: $transfer->amount,
                source: $transfer,
                note: $transfer->note,
            );

            return $transfer;
        });
    }

    public function show(WalletTransfer $walletTransfer): WalletTransfer
    {
        return $walletTransfer->load(['fromWallet', 'toWallet']);
    }

    public function delete(WalletTransfer $walletTransfer): void
    {
        $walletTransfer->delete();
    }
}
