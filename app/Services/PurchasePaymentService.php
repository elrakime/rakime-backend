<?php

namespace App\Services;

use App\Enums\PurchaseStatus;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class PurchasePaymentService
{
    public function __construct(private readonly WalletService $walletService) {}

    public function list(Purchase $purchase): \Illuminate\Database\Eloquent\Collection
    {
        return $purchase->payments()->orderBy('paid_at', 'desc')->get();
    }

    public function create(Purchase $purchase, array $data): PurchasePayment
    {
        if ($purchase->status === PurchaseStatus::DRAFT) {
            throw new \Exception(__('purchases.must_be_received'), 422);
        }

        $remaining = $purchase->total_amount - $purchase->paid_amount;

        if ($data['amount'] > $remaining) {
            throw new \Exception(__('purchases.amount_exceeds_remaining'), 422);
        }

        return DB::transaction(function () use ($purchase, $data) {
            $payment = PurchasePayment::create([
                'purchase_id' => $purchase->id,
                'amount'      => $data['amount'],
                'paid_at'     => $data['paid_at'],
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

            return $payment;
        });
    }
}
