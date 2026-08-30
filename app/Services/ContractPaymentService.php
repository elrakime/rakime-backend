<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ContractStatus;
use App\Enums\InstallmentPaymentMethod;
use App\Enums\InstallmentStatus;
use App\Models\Contract;
use App\Models\ContractEarlyCancelation;
use App\Models\ContractPayment;
use Exception;
use Illuminate\Support\Facades\DB;

class ContractPaymentService
{
    /**
     * Record a cash payment against a configured/active contract.
     *
     * The amount must be an exact positive multiple of the contract's
     * monthly_amount. The oldest unpaid installments are settled (marked CASH),
     * and an early-cancellation history row is created with the due_date of the
     * last installment covered by this payment.
     */
    public function create(Contract $contract, float $amount, ?string $note = null): ContractPayment
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

        return DB::transaction(function () use ($contract, $amount, $note, $count) {
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

            return $payment->load(['contract', 'installments']);
        });
    }
}
