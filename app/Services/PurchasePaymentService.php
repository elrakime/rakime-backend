<?php

namespace App\Services;

use App\Enums\PurchasePaymentStatus;
use App\Enums\PurchaseStatus;
use App\Enums\WalletMovementType;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\Wallet;
use App\Models\WalletMovement;
use Exception;
use Illuminate\Support\Facades\DB;

class PurchasePaymentService
{
    public function __construct(private readonly WalletService $walletService) {}

    public function list(Purchase $purchase): \Illuminate\Database\Eloquent\Collection
    {
        return $purchase->payments()->orderBy('created_at', 'desc')->get();
    }

    public function create(Purchase $purchase, array $data): PurchasePayment
    {
        if ($purchase->status !== PurchaseStatus::COMPLETED) {
            throw new Exception(__('purchases.must_be_completed'), 422);
        }

        $remaining = $purchase->net_amount - $purchase->paid_amount;

        if ($data['amount'] > $remaining) {
            throw new Exception(__('purchases.amount_exceeds_remaining'), 422);
        }

        return DB::transaction(function () use ($purchase, $data) {
            $payment = PurchasePayment::create([
                'purchase_id' => $purchase->id,
                'amount'      => $data['amount'],
            ]);

            $wallet = Wallet::findOrFail($this->resolveWalletId($purchase, $data['wallet_id'] ?? null));

            $this->walletService->purchasePayment(
                wallet: $wallet,
                amount: $data['amount'],
                source: $payment,
                note: $data['note'] ?? null,
            );

            $purchase->recalculateAmounts();

            return $payment->fresh();
        });
    }

    public function cancel(Purchase $purchase, PurchasePayment $payment): PurchasePayment
    {
        if ($purchase->status !== PurchaseStatus::COMPLETED) {
            throw new Exception(__('purchases.must_be_completed'), 422);
        }

        return DB::transaction(function () use ($purchase, $payment) {
            $movement = WalletMovement::where('source_type', PurchasePayment::class)
                ->where('source_id', $payment->id)
                ->where('movement_type', WalletMovementType::PURCHASE_PAYMENT)
                ->first();

            if ($movement) {
                $wallet = $movement->wallet;

                $this->walletService->paymentCancel(
                    wallet: $wallet,
                    amount: $payment->amount,
                    source: $payment,
                    note: __('purchases.payment_canceled_note', ['amount' => $payment->amount]),
                );
            }

            $payment->update(['status' => PurchasePaymentStatus::CANCELED]);

            $purchase->recalculateAmounts();

            return $payment->refresh();
        });
    }

    private function resolveWalletId(Purchase $purchase, ?int $walletId): int
    {
        if ($walletId) {
            return $walletId;
        }

        $wallets = $purchase->branch?->wallets()->get() ?? collect();

        if ($wallets->isEmpty()) {
            throw new Exception(__('wallets.no_branch_wallets'), 422);
        }

        if ($wallets->count() > 1) {
            throw new Exception(__('wallets.multiple_branch_wallets'), 422);
        }

        return $wallets->first()->id;
    }
}
