<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasUserstamps;

class WalletTransfer extends Model
{
    use HasUserstamps;

    protected $fillable = [
        'from_wallet_id',
        'to_wallet_id',
        'amount',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function scopeByUserBranches(Builder $query): void
    {
        $user = auth()->user();

        if (! $user || $user->hasRole(Role::ADMIN->value)) {
            return;
        }

        $branchIds = $user->branches()->pluck('branch_id');

        if ($branchIds->isNotEmpty()) {
            $query->where(function (Builder $q) use ($branchIds) {
                $walletBranch = fn (Builder $sub) => $sub->where('owner_type', Branch::class)
                    ->whereIn('owner_id', $branchIds);

                $q->whereHas('fromWallet', $walletBranch)
                  ->orWhereHas('toWallet', $walletBranch);
            });
        }
    }

    public function branchIds(): array
    {
        return array_filter([
            $this->fromWallet?->owner_type === Branch::class ? $this->fromWallet->owner_id : null,
            $this->toWallet?->owner_type === Branch::class ? $this->toWallet->owner_id : null,
        ]);
    }

    public function fromWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'from_wallet_id');
    }

    public function toWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'to_wallet_id');
    }
}
