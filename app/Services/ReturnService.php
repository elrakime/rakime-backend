<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Models\InventoryMovement;
use App\Models\PurchaseReturn;
use App\Models\ReturnItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class PurchaseReturnService
{
    public function list(Request $request): LengthAwarePaginator
    {
        return QueryBuilder::for(PurchaseReturn::class, $request)
            ->with(['purchase', 'items.purchaseItem.product'])
            ->allowedFilters(
                AllowedFilter::exact('purchase_id'),
                AllowedFilter::partial('reference'),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('reference', 'like', "%{$value}%");
                    });
                }),
            )
            ->allowedSorts(
                AllowedSort::field('reference'),
                AllowedSort::field('returned_at'),
                AllowedSort::field('created_at'),
            )
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 15))
            ->appends($request->query());
    }

    public function create(array $data): PurchaseReturn
    {
        return DB::transaction(function () use ($data) {
            $purchaseReturn = PurchaseReturn::create([
                'purchase_id' => $data['purchase_id'],
                'reference'   => $data['reference'] ?? null,
                'returned_at' => $data['returned_at'] ?? now(),
            ]);

            foreach ($data['items'] as $item) {
                $returnItem = ReturnItem::create([
                    'purchase_return_id' => $purchaseReturn->id,
                    'purchase_item_id'   => $item['purchase_item_id'],
                    'quantity'           => $item['quantity'],
                    'reason'             => $item['reason'] ?? null,
                ]);

                // Find the stock and batch linked to this purchase item
                $purchaseItem = $returnItem->purchaseItem;
                $stock = $purchaseItem->stock;

                if ($stock) {
                    // Decrement the batch quantity
                    /** @var \App\Models\Batch|null $batch */
                    $batch = $stock->batches()
                        ->where('source_id', $purchaseItem->id)
                        ->where('source_type', 'purchase_item')
                        ->first();

                    if ($batch) {
                        $batch->decrement('current_quantity', $item['quantity']);
                    }

                    InventoryMovement::create([
                        'stock_id'      => $stock->id,
                        'batch_id'      => $batch?->id,
                        'inventory_id'  => $stock->inventory_id,
                        'product_id'    => $purchaseItem->product_id,
                        'moveable_id'   => $purchaseReturn->id,
                        'movement_type' => InventoryMovementType::RETURN,
                        'quantity'      => $item['quantity'],
                    ]);
                }
            }

            return $purchaseReturn->load(['purchase', 'items.purchaseItem.product']);
        });
    }

    public function show(PurchaseReturn $purchaseReturn): PurchaseReturn
    {
        return $purchaseReturn->loadMissing(['purchase', 'items.purchaseItem.product']);
    }

    public function delete(PurchaseReturn $purchaseReturn): void
    {
        DB::transaction(function () use ($purchaseReturn) {
            $purchaseReturn->items()->delete();
            $purchaseReturn->inventoryMovements()->delete();
            $purchaseReturn->delete();
        });
    }
}
