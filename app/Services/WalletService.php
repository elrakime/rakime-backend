<?php

namespace App\Services;

use App\Enums\WalletMovementType;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Wallet;
use App\Models\WalletMovement;
use App\Traits\ScopesByUserBranches;
use Exception;
use Illuminate\Database\Eloquent\Model;
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

        $owner = match ($data['owner_type'] ?? null) {
            'branch' => Branch::findOrFail($data['owner_id']),
            'account' => Account::findOrFail($data['owner_id']),
            default => null
        };

        $wallet = Wallet::create([
            'owner_type' => $owner ? get_class($owner) : null,
            'owner_id' => $owner?->id ?? null,
            'name' => $data['name'],
            'balance' => $data['balance'] ?? 0,
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
        ], fn($v) => $v !== null));

        return $wallet->refresh()->loadMissing('owner');
    }

    public function delete(Wallet $wallet): void
    {
        if ((float) $wallet->balance !== 0.0) {
            throw new Exception(__('wallets.cannot_delete_with_balance'), 422);
        }

        if ($wallet->owner) {
            throw new Exception(__('wallets.cannot_delete_with_owner'), 422);
        }

        $wallet->delete();
    }

    // ============================================================
    // MOVEMENT METHODS
    // ============================================================

    /**
     * Add funds to a wallet (inflow).
     */
    public function deposit(
        Wallet $wallet,
        int|float|string $amount,
        ?string $note = null,
    ): WalletMovement {
        return $this->recordMovement(
            $wallet,
            WalletMovementType::DEPOSIT,
            $amount,
            $note,
        );
    }

    /**
     * Remove funds from a wallet (outflow). Throws if insufficient balance.
     */
    public function withdraw(
        Wallet $wallet,
        int|float|string $amount,
        ?string $note = null,
    ): WalletMovement {
        $this->guardSufficientBalance($wallet, $amount);

        return $this->recordMovement(
            $wallet,
            WalletMovementType::WITHDRAWAL,
            -abs((float) $amount),
            $note,
        );
    }

    /**
     * Record an expense deduction.
     */
    public function expense(
        Wallet $wallet,
        int|float|string $amount,
        ?string $note = null,
    ): WalletMovement {
        $this->guardSufficientBalance($wallet, $amount);

        return $this->recordMovement(
            $wallet,
            WalletMovementType::EXPENSE,
            -abs((float) $amount),
            $note,
        );
    }

    /**
     * Record a salary payment deduction.
     */
    public function salary(
        Wallet $wallet,
        int|float|string $amount,
        ?string $note = null,
    ): WalletMovement {
        $this->guardSufficientBalance($wallet, $amount);

        return $this->recordMovement(
            $wallet,
            WalletMovementType::SALARY,
            -abs((float) $amount),
            $note,
        );
    }

    /**
     * Record a balance adjustment (positive or negative).
     */
    public function adjustment(
        Wallet $wallet,
        int|float|string $amount,
        ?string $note = null,
    ): WalletMovement {
        $amount = (float) $amount;

        if ($amount < 0) {
            $this->guardSufficientBalance($wallet, abs($amount));
        }

        return $this->recordMovement(
            $wallet,
            WalletMovementType::ADJUSTMENT,
            $amount,
            $note,
        );
    }

    /**
     * Record a transfer out from a wallet.
     */
    public function transferOut(
        Wallet $wallet,
        int|float|string $amount,
        Model $source,
        ?string $note = null,
    ): WalletMovement {
        $this->guardSufficientBalance($wallet, $amount);

        return $this->recordMovement(
            $wallet,
            WalletMovementType::TRANSFER_OUT,
            -abs((float) $amount),
            $note,
            $source,
        );
    }

    /**
     * Record a transfer in to a wallet.
     */
    public function transferIn(
        Wallet $wallet,
        int|float|string $amount,
        Model $source,
        ?string $note = null,
    ): WalletMovement {
        return $this->recordMovement(
            $wallet,
            WalletMovementType::TRANSFER_IN,
            $amount,
            $note,
            $source,
        );
    }

    /**
     * Record a purchase payment deduction.
     */
    public function purchasePayment(
        Wallet $wallet,
        int|float|string $amount,
        Model $source,
        ?string $note = null,
    ): WalletMovement {
        $this->guardSufficientBalance($wallet, $amount);

        return $this->recordMovement(
            $wallet,
            WalletMovementType::PURCHASE_PAYMENT,
            -abs((float) $amount),
            $note,
            $source,
        );
    }

    /**
     * Record a purchase return credit (inflow).
     */
    public function purchaseReturn(
        Wallet $wallet,
        int|float|string $amount,
        Model $source,
        ?string $note = null,
    ): WalletMovement {
        return $this->recordMovement(
            $wallet,
            WalletMovementType::PURCHASE_RETURN,
            $amount,
            $note,
            $source,
        );
    }

    /**
     * Record a sale return deduction (outflow).
     */
    public function saleReturn(
        Wallet $wallet,
        int|float|string $amount,
        Model $source,
        ?string $note = null,
    ): WalletMovement {
        $this->guardSufficientBalance($wallet, $amount);

        return $this->recordMovement(
            $wallet,
            WalletMovementType::SALE_RETURN,
            -abs((float) $amount),
            $note,
            $source,
        );
    }

    /**
     * Record a sale update adjustment (positive = credit, negative = debit).
     */
    public function saleUpdate(
        Wallet $wallet,
        int|float|string $amount,
        Model $source,
        ?string $note = null,
    ): WalletMovement {
        $amount = (float) $amount;

        if ($amount < 0) {
            $this->guardSufficientBalance($wallet, abs($amount));
        }

        return $this->recordMovement(
            $wallet,
            WalletMovementType::SALE_UPDATE,
            $amount,
            $note,
            $source,
        );
    }

    /**
     * Record a payment cancellation credit (inflow).
     */
    public function paymentCancel(
        Wallet $wallet,
        int|float|string $amount,
        Model $source,
        ?string $note = null,
    ): WalletMovement {
        return $this->recordMovement(
            $wallet,
            WalletMovementType::PAYMENT_CANCEL,
            $amount,
            $note,
            $source,
        );
    }

    /**
     * Record a sale payment credit (inflow).
     */
    public function salePayment(
        Wallet $wallet,
        int|float|string $amount,
        Model $source,
        ?string $note = null,
    ): WalletMovement {
        return $this->recordMovement(
            $wallet,
            WalletMovementType::SALE_PAYMENT,
            $amount,
            $note,
            $source,
        );
    }

    /**
     * Record an installment payment deduction.
     */
    public function installmentPayment(
        Wallet $wallet,
        int|float|string $amount,
        Model $source,
        ?string $note = null,
    ): WalletMovement {
        $this->guardSufficientBalance($wallet, $amount);

        return $this->recordMovement(
            $wallet,
            WalletMovementType::INSTALLMENT_PAYMENT,
            -abs((float) $amount),
            $note,
            $source,
        );
    }

    // ============================================================
    // INTERNAL
    // ============================================================

    /**
     * Core: increment/decrement wallet balance and create movement record.
     */
    private function recordMovement(
        Wallet $wallet,
        WalletMovementType $type,
        int|float|string $amount,
        ?string $note,
        ?Model $source = null,
    ): WalletMovement {
        $amount = (float) $amount;
        $oldBalance = (float) $wallet->balance;

        if ($amount > 0) {
            $wallet->increment('balance', $amount);
        } elseif ($amount < 0) {
            $wallet->decrement('balance', abs($amount));
        }

        $newBalance = $oldBalance + $amount;

        return WalletMovement::create([
            'wallet_id' => $wallet->id,
            'movement_type' => $type,
            'amount' => $amount,
            'old_balance' => $oldBalance,
            'new_balance' => $newBalance,
            'source_type' => $source ? get_class($source) : null,
            'source_id' => $source?->id,
            'note' => $note,
        ]);
    }

    private function guardSufficientBalance(Wallet $wallet, int|float|string $amount): void
    {
        if ((float) $wallet->balance < (float) $amount) {
            throw new Exception(__('wallet_transfers.insufficient_balance'), 422);
        }
    }
}
