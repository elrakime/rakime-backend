<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ContractStatus;
use App\Enums\InstallmentPaymentMethod;
use App\Enums\InstallmentStatus;
use App\Models\Branch;
use App\Models\Contract;
use App\Models\ContractEarlyCancelation;
use App\Models\ContractPayment;
use App\Models\Wallet;
use Exception;
use Illuminate\Support\Facades\DB;

class ContractPaymentService
{
    public function __construct(private readonly WalletService $walletService) {}

    /**
     * Record a cash payment against a configured/active contract.
     *
     * The amount must be an exact positive multiple of the contract's
     * monthly_amount. The oldest unpaid installments are settled (marked CASH),
     * and an early-cancellation history row is created with the due_date of the
     * last installment covered by this payment.
     *
     * @param int|null $walletId Optional wallet to credit. When omitted, the
     *                           contract's branch wallet is used.
     */
    public function create(Contract $contract, float $amount, ?string $note = null, ?int $walletId = null): ContractPayment
    {
        $monthlyAmount = (float) $contract->monthly_amount;

        if ($monthlyAmount <= 0) {
            throw new Exception(__('payments.missing_monthly_amount'), 422);
        }

        $count = (int) round($amount / $monthlyAmount);

        if ($count < 1 || abs($amount - ($count * $monthlyAmount)) > 0.001) {
            throw new Exception(__('payments.amount_not_multiple'), 422);
        }

        if ($contract->status !== ContractStatus::CONFIGURED && $contract->status !== ContractStatus::ACTIVE) {
            throw new Exception(__('payments.contract_not_payable'), 422);
        }

        return DB::transaction(function () use ($contract, $amount, $note, $count, $walletId) {
            $installments = $contract->installments()
                ->where('status', InstallmentStatus::UNPAID)
                ->orderBy('due_date')
                ->lockForUpdate()
                ->limit($count)
                ->get();

            if ($installments->count() < $count) {
                throw new Exception(__('payments.not_enough_unpaid'), 422);
            }

            $payment = ContractPayment::create([
                'contract_id' => $contract->id,
                'amount'      => $amount,
                'note'        => $note,
            ]);

            foreach ($installments as $installment) {
                $payment->installments()->attach($installment->id);

                $installment->update([
                    'status'         => InstallmentStatus::PAID,
                    'payment_method' => InstallmentPaymentMethod::CASH->value,
                ]);
            }

            $lastCovered = $installments->last();

            ContractEarlyCancelation::create([
                'contract_id' => $contract->id,
                'payment_id'  => $payment->id,
                'end_date'    => $lastCovered->due_date,
            ]);

            $this->creditWallet($contract, $amount, $payment, $walletId);

            return $payment->load(['contract', 'installments']);
        });
    }

    /**
     * Credit the payment amount to the target wallet (explicit or branch wallet).
     */
    private function creditWallet(Contract $contract, float $amount, ContractPayment $payment, ?int $walletId): void
    {
        $wallet = $this->resolveWallet($contract, $walletId);

        if ($wallet === null) {
            return;
        }

        $this->walletService->contractPayment(
            wallet: $wallet,
            amount: $amount,
            source: $payment,
            note: $payment->note,
        );
    }

    /**
     * Resolve the wallet to credit: explicit wallet_id, else the contract's branch wallet.
     */
    private function resolveWallet(Contract $contract, ?int $walletId): ?Wallet
    {
        if ($walletId !== null) {
            return Wallet::find($walletId);
        }

        return Wallet::where('owner_type', Branch::class)
            ->where('owner_id', $contract->branch_id)
            ->first();
    }
}
