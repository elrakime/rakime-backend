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
        if ($purchase->status === PurchaseStatus::PENDING) {
            throw new Exception(__('purchases.must_be_received'), 422);
        }

        $remaining = $purchase->total_amount - $purchase->paid_amount;

        if ($data['amount'] > $remaining) {
            throw new Exception(__('purchases.amount_exceeds_remaining'), 422);
        }

        return DB::transaction(function () use ($purchase, $data) {
            $payment = PurchasePayment::create([
                'purchase_id' => $purchase->id,
                'amount'      => $data['amount'],
            ]);

            $newPaid = $purchase->paid_amount + $data['amount'];
            $status  = $newPaid >= $purchase->total_amount
                ? PurchaseStatus::PAID
                : PurchaseStatus::PARTIALLY_PAID;

            $purchase->update([
                'paid_amount' => $newPaid,
                'status'      => $status,
            ]);

            $wallet = Wallet::findOrFail($data['wallet_id']);

            $this->walletService->purchasePayment(
                wallet: $wallet,
                amount: $data['amount'],
                source: $payment,
                note: $data['note'] ?? null,
            );

            return $payment->fresh();
        });
    }

    public function cancel(Purchase $purchase, PurchasePayment $payment): PurchasePayment
    {
        if ($purchase->status === PurchaseStatus::PENDING) {
            throw new Exception(__('purchases.must_be_received'), 422);
        }

        return DB::transaction(function () use ($purchase, $payment) {
            $newPaid = $purchase->paid_amount - $payment->amount;
            $status  = $newPaid <= 0
                ? PurchaseStatus::RECEIVED
                : ($newPaid >= $purchase->total_amount
                    ? PurchaseStatus::PAID
                    : PurchaseStatus::PARTIALLY_PAID);

            $purchase->update([
                'paid_amount' => max(0, $newPaid),
                'status'      => $status,
            ]);

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

            return $payment->refresh();
        });
    }
}
