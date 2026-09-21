<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\InstallmentStatus;
use App\Models\Batch;
use App\Models\Contract;
use App\Models\Purchase;
use App\Models\Wallet;

class ZakatService
{
    public const ZAKAT_RATE = 0.025; // 2.5%

    /**
     * Compute the zakat breakdown and total.
     *
     * Zakat base =
     *   wallet total
     *   + all batches (quantity * purchase price)
     *   + total unpaid installments (excluding risky contracts)
     *   - total unpaid purchases
     *
     * Zakat amount = base * 2.5%
     */
    public function compute(): array
    {
        $walletTotal          = $this->walletTotal();
        $batchesValue         = $this->batchesValue();
        $unpaidInstallments   = $this->unpaidInstallmentsExcludingRisky();
        $unpaidPurchases      = $this->unpaidPurchases();

        $base = $walletTotal + $batchesValue + $unpaidInstallments - $unpaidPurchases;

        $zakat = round($base * self::ZAKAT_RATE, 2);

        return [
            'wallet_total'           => round($walletTotal, 2),
            'batches_value'          => round($batchesValue, 2),
            'unpaid_installments'    => round($unpaidInstallments, 2),
            'unpaid_purchases'       => round($unpaidPurchases, 2),
            'zakat_base'             => round($base, 2),
            'zakat_rate'             => self::ZAKAT_RATE,
            'zakat_amount'           => $zakat,
        ];
    }

    /**
     * Sum of all wallet balances.
     */
    private function walletTotal(): float
    {
        return (float) Wallet::query()->sum('balance');
    }

    /**
     * Sum of (current_quantity * purchase_price) across all batches.
     */
    private function batchesValue(): float
    {
        return (float) Batch::query()
            ->get()
            ->sum(fn (Batch $batch) => (float) $batch->current_quantity * (float) $batch->purchase_price);
    }

    /**
     * Total amount of unpaid installments, excluding installments belonging to
     * "risky" contracts. A risky contract has at least one installment that is
     * unpaid (or partially paid) and whose due date has already passed.
     */
    private function unpaidInstallmentsExcludingRisky(): float
    {
        $riskyContractIds = $this->riskyContractIds();

        return (float) \App\Models\Installment::query()
            ->where('status', InstallmentStatus::UNPAID->value)
            ->when($riskyContractIds->isNotEmpty(), fn ($q) => $q->whereNotIn('contract_id', $riskyContractIds))
            ->sum('amount');
    }

    /**
     * IDs of contracts considered "risky": at least one installment is unpaid
     * (or partially paid) and its due date has passed.
     */
    private function riskyContractIds(): \Illuminate\Support\Collection
    {
        return Contract::query()
            ->whereHas('installments', function ($query) {
                $query->whereIn('status', [
                    InstallmentStatus::UNPAID->value,
                    InstallmentStatus::PARTIALLY_PAID->value,
                ])->whereDate('due_date', '<', now()->toDateString());
            })
            ->pluck('id');
    }

    /**
     * Total unpaid amount across purchases: sum(net_amount - paid_amount) where
     * there is still an outstanding balance.
     */
    private function unpaidPurchases(): float
    {
        return (float) Purchase::query()
            ->get()
            ->sum(fn (Purchase $purchase) => max(0.0, (float) $purchase->net_amount - (float) $purchase->paid_amount));
    }
}
