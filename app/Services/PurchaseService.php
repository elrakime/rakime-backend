<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Enums\PurchaseStatus;
use App\Enums\TreasuryMovementType;
use App\Models\InventoryMovement;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\Stock;
use App\Models\Treasury;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class PurchaseService
{
    public function list(Request $request): LengthAwarePaginator
    {
        return QueryBuilder::for(Purchase::class, $request)
            ->with(['supplier', 'items.product'])
            ->allowedFilters(
                AllowedFilter::partial('reference'),
                AllowedFilter::exact('supplier_id'),
                AllowedFilter::exact('status'),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('reference', 'like', "%{$value}%");
                    });
                }),
            )
            ->allowedSorts(
                AllowedSort::field('total_amount'),
                AllowedSort::field('purchased_at'),
                AllowedSort::field('created_at'),
            )
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 15))
            ->appends($request->query());
    }

    public function create(array $data): Purchase
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'];

            $totalAmount = collect($items)->sum(fn ($item) => $item['quantity'] * $item['price']);

            $purchase = Purchase::create([
                'supplier_id'  => $data['supplier_id'],
                'reference'    => $data['reference'] ?? null,
                'status'       => PurchaseStatus::DRAFT,
                'total_amount' => $totalAmount,
                'paid_amount'  => 0,
                'note'         => $data['note'] ?? null,
                'purchased_at' => $data['purchased_at'],
            ]);

            $purchase->items()->createMany(
                collect($items)->map(fn ($item) => [
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                    'price'      => $item['price'],
                ])->all()
            );

            return $purchase->loadMissing(['supplier', 'items.product']);
        });
    }

    public function show(Purchase $purchase): Purchase
    {
        return $purchase->loadMissing(['supplier', 'items.product', 'payments']);
    }

    public function update(Purchase $purchase, array $data): Purchase
    {
        if ($purchase->status !== PurchaseStatus::DRAFT) {
            throw ValidationException::withMessages([
                'status' => [__('purchases.not_draft')],
            ]);
        }

        return DB::transaction(function () use ($purchase, $data) {
            $purchase->update(array_filter([
                'supplier_id'  => $data['supplier_id'] ?? null,
                'reference'    => $data['reference'] ?? null,
                'note'         => $data['note'] ?? null,
                'purchased_at' => $data['purchased_at'] ?? null,
            ], fn ($v) => $v !== null));

            if (array_key_exists('items', $data)) {
                $items = $data['items'];

                $totalAmount = collect($items)->sum(fn ($item) => $item['quantity'] * $item['price']);

                $purchase->items()->delete();
                $purchase->items()->createMany(
                    collect($items)->map(fn ($item) => [
                        'product_id' => $item['product_id'],
                        'quantity'   => $item['quantity'],
                        'price'      => $item['price'],
                    ])->all()
                );

                $purchase->update(['total_amount' => $totalAmount]);
            }

            return $purchase->refresh()->loadMissing(['supplier', 'items.product', 'payments']);
        });
    }

    public function delete(Purchase $purchase): void
    {
        if ($purchase->status !== PurchaseStatus::DRAFT) {
            throw ValidationException::withMessages([
                'status' => [__('purchases.not_draft')],
            ]);
        }

        $purchase->delete();
    }

    public function receive(Purchase $purchase, array $data): Purchase
    {
        if ($purchase->status !== PurchaseStatus::DRAFT) {
            throw ValidationException::withMessages([
                'status' => [__('purchases.not_draft')],
            ]);
        }

        return DB::transaction(function () use ($purchase, $data) {
            $purchase->update([
                'status'      => PurchaseStatus::RECEIVED,
                'received_at' => $data['received_at'] ?? Carbon::now(),
            ]);

            $inventoryId = $data['inventory_id'];

            // Index optional per-item pricing overrides by product_id
            $pricingByProduct = collect($data['items'] ?? [])->keyBy('product_id');

            foreach ($purchase->items as $item) {
                $pricing = $pricingByProduct->get($item->product_id, []);

                $stock = Stock::create([
                    'inventory_id' => $inventoryId,
                    'product_id'   => $item->product_id,
                ]);

                $batch = $stock->batches()->create([
                    'source_id'        => $item->id,
                    'source_type'      => 'purchase_items',
                    'purchase_price'   => $item->price,
                    'initial_quantity' => $item->quantity,
                    'current_quantity' => $item->quantity,
                    'purchased_at'     => $purchase->purchased_at,
                ]);

                $prices = [];
                if (isset($pricing['selling_price'])) {
                    $prices[] = new \App\Models\Price(['type' => 'selling', 'amount' => $pricing['selling_price']]);
                }
                if (isset($pricing['installment_price'])) {
                    $prices[] = new \App\Models\Price(['type' => 'installment', 'amount' => $pricing['installment_price']]);
                }
                if (!empty($prices)) {
                    $stock->prices()->saveMany($prices);
                }

                InventoryMovement::create([
                    'stock_id'      => $stock->id,
                    'batch_id'      => $batch->id,
                    'inventory_id'  => $inventoryId,
                    'product_id'    => $item->product_id,
                    'moveable_id'   => $purchase->id,
                    'movement_type' => InventoryMovementType::RECEIVE,
                    'quantity'      => $item->quantity,
                ]);
            }

            return $purchase->refresh()->loadMissing(['supplier', 'items.product', 'payments']);
        });
    }

    public function listPayments(Purchase $purchase): \Illuminate\Database\Eloquent\Collection
    {
        return $purchase->payments()->orderBy('paid_at', 'desc')->get();
    }

    public function addPayment(Purchase $purchase, array $data): PurchasePayment
    {
        if ($purchase->status === PurchaseStatus::DRAFT) {
            throw ValidationException::withMessages([
                'status' => [__('purchases.must_be_received')],
            ]);
        }

        $remaining = $purchase->total_amount - $purchase->paid_amount;

        if ($data['amount'] > $remaining) {
            throw ValidationException::withMessages([
                'amount' => [__('purchases.amount_exceeds_remaining')],
            ]);
        }

        return DB::transaction(function () use ($purchase, $data) {
            $payment = PurchasePayment::create([
                'purchase_id'    => $purchase->id,
                'amount'         => $data['amount'],
                'payment_method' => $data['payment_method'],
                'paid_at'        => $data['paid_at'],
            ]);

            $newPaid = $purchase->paid_amount + $data['amount'];
            $status  = $newPaid >= $purchase->total_amount
                ? PurchaseStatus::PAID
                : PurchaseStatus::PARTIALLY_PAID;

            $purchase->update([
                'paid_amount' => $newPaid,
                'status'      => $status,
            ]);

            $treasury = Treasury::findOrFail($data['treasury_id']);
            $treasury->decrement('balance', $data['amount']);

            $treasury->movements()->create([
                'movement_type'  => TreasuryMovementType::PURCHASE_PAYMENT,
                'amount'         => -$data['amount'],
                'reference_type' => 'purchase_payments',
                'reference_id'   => $payment->id,
                'note'           => $data['note'] ?? null,
                'performed_by'   => Auth::id(),
            ]);

            return $payment;
        });
    }
}
