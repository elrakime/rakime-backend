<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Enums\PriceType;
use App\Enums\PurchaseStatus;
use App\Models\InventoryMovement;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Stock;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class PurchaseService
{
    public function list(Request $request): LengthAwarePaginator
    {
        return QueryBuilder::for(Purchase::class, $request)
            ->with(['supplier', 'items.product', 'inventory'])
            ->allowedFilters(
                AllowedFilter::partial('reference'),
                AllowedFilter::exact('supplier_id'),
                AllowedFilter::exact('inventory_id'),
                AllowedFilter::exact('status'),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('reference', 'like', "%{$value}%");
                    });
                }),
            )
            ->allowedSorts(
                AllowedSort::field('total_amount'),
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
                'status'       => PurchaseStatus::PENDING,
                'total_amount' => $totalAmount,
                'paid_amount'  => 0,
                'note'         => $data['note'] ?? null,
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
        return $purchase->loadMissing(['supplier', 'items.product', 'payments', 'inventory']);
    }

    public function update(Purchase $purchase, array $data): Purchase
    {
        if ($purchase->status !== PurchaseStatus::PENDING) {
            throw new Exception(__('purchases.not_draft'), 422);
        }

        return DB::transaction(function () use ($purchase, $data) {
            $purchase->update(array_filter([
                'supplier_id'  => $data['supplier_id'] ?? null,
                'note'         => $data['note'] ?? null,
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
        if ($purchase->status !== PurchaseStatus::PENDING) {
            throw new Exception(__('purchases.not_draft'), 422);
        }

        $purchase->delete();
    }

    public function receive(Purchase $purchase, array $data): Purchase
    {
        if ($purchase->status !== PurchaseStatus::PENDING) {
            throw new Exception(__('purchases.not_draft'), 422);
        }

        return DB::transaction(function () use ($purchase, $data) {
            $purchase->update([
                'status'       => PurchaseStatus::RECEIVED,
                'inventory_id' => $data['inventory_id'],
            ]);

            $inventoryId = $data['inventory_id'];

            // Index optional per-item pricing overrides by product_id
            $pricingByProduct = collect($data['items'] ?? [])->keyBy('product_id');

            foreach ($purchase->items as $item) {
                $pricing = $pricingByProduct->get($item->product_id, []);

                $stock = Stock::firstOrCreate([
                    'inventory_id' => $inventoryId,
                    'product_id'   => $item->product_id,
                ]);

                $batch = $stock->batches()->create([
                    'source_id'        => $item->id,
                    'source_type'      => PurchaseItem::class,
                    'purchase_price'   => $item->price,
                    'initial_quantity' => $item->quantity,
                    'current_quantity' => $item->quantity,
                ]);

                $prices = collect();
                foreach ($pricing['selling_prices'] ?? [] as $amount) {
                    $prices->push(new \App\Models\Price(['type' => PriceType::SELLING, 'amount' => $amount]));
                }
                foreach ($pricing['installment_prices'] ?? [] as $amount) {
                    $prices->push(new \App\Models\Price(['type' => PriceType::INSTALLMENT, 'amount' => $amount]));
                }

                if ($prices->isNotEmpty()) {
                    $stock->prices()->saveMany($prices);
                }

                InventoryMovement::create([
                    'stock_id'      => $stock->id,
                    'inventory_id'  => $inventoryId,
                    'product_id'    => $item->product_id,
                    'source_id'   => $purchase->id,
                    'movement_type' => InventoryMovementType::RECEIVE,
                    'quantity'      => $item->quantity,
                ]);
            }

            return $purchase->refresh()->loadMissing(['supplier', 'items.product', 'payments']);
        });
    }

}
