<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Models\Batch;
use App\Models\InventoryMovement;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class PurchaseReturnService
{
    public function list(Request $request, Purchase $purchase): LengthAwarePaginator
    {
        return QueryBuilder::for(PurchaseReturn::class, $request)
            ->where('purchase_id', $purchase->id)
            ->with(['purchase', 'items.purchaseItem.product'])
            ->allowedFilters(
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

    public function create(Purchase $purchase, array $data): PurchaseReturn
    {
        $this->validateItemsBelongToPurchase($purchase->id, $data['items']);

        return DB::transaction(function () use ($purchase, $data) {
            $purchaseReturn = PurchaseReturn::create([
                'purchase_id' => $purchase->id,
                'note'        => $data['note'] ?? null,
                'returned_at' => $data['returned_at'] ?? now(),
            ]);

            foreach ($data['items'] as $item) {
                PurchaseReturnItem::create([
                    'purchase_return_id' => $purchaseReturn->id,
                    'purchase_item_id'   => $item['purchase_item_id'],
                    'quantity'           => $item['quantity'],
                    'reason'             => $item['reason'] ?? null,
                ]);
            }

            return $purchaseReturn->load(['purchase', 'items.purchaseItem.product']);
        });
    }

    public function update(PurchaseReturn $purchaseReturn, array $data): PurchaseReturn
    {
        if ($purchaseReturn->approved_at) {
            throw new \Exception(__('purchase_returns.cannot_update_approved'), 422);
        }

        if (isset($data['items'])) {
            $purchaseReturn->load('purchase');
            $this->validateItemsBelongToPurchase($purchaseReturn->purchase_id, $data['items']);
        }

        return DB::transaction(function () use ($purchaseReturn, $data) {
            $purchaseReturn->update([
                'note'        => $data['note'] ?? $purchaseReturn->note,
                'returned_at' => $data['returned_at'] ?? $purchaseReturn->returned_at,
            ]);

            if (isset($data['items'])) {
                $purchaseReturn->items()->delete();

                foreach ($data['items'] as $item) {
                    PurchaseReturnItem::create([
                        'purchase_return_id' => $purchaseReturn->id,
                        'purchase_item_id'   => $item['purchase_item_id'],
                        'quantity'           => $item['quantity'],
                        'reason'             => $item['reason'] ?? null,
                    ]);
                }
            }

            return $purchaseReturn->fresh()->loadMissing(['purchase', 'items.purchaseItem.product']);
        });
    }

    public function show(PurchaseReturn $purchaseReturn): PurchaseReturn
    {
        return $purchaseReturn->loadMissing(['purchase', 'items.purchaseItem.product']);
    }

    public function approve(PurchaseReturn $purchaseReturn): PurchaseReturn
    {
        if ($purchaseReturn->approved_at) {
            return $purchaseReturn;
        }

        return DB::transaction(function () use ($purchaseReturn) {
            $purchaseReturn->update(['approved_at' => now()]);

            foreach ($purchaseReturn->items as $returnItem) {
                $purchaseItem = $returnItem->purchaseItem;

                $batch = Batch::where('source_id', $purchaseItem->id)
                    ->where('source_type', 'purchase_items')
                    ->first();

                if ($batch) {
                    $batch->decrement('current_quantity', $returnItem->quantity);

                    InventoryMovement::create([
                        'stock_id'      => $batch->stock_id,
                        'batch_id'      => $batch->id,
                        'inventory_id'  => $batch->stock->inventory_id,
                        'product_id'    => $purchaseItem->product_id,
                        'source_id'   => $purchaseReturn->id,
                        'movement_type' => InventoryMovementType::RETURN,
                        'quantity'      => $returnItem->quantity,
                    ]);
                }
            }

            return $purchaseReturn->fresh()->loadMissing(['purchase', 'items.purchaseItem.product']);
        });
    }

    public function delete(PurchaseReturn $purchaseReturn): void
    {
        if ($purchaseReturn->approved_at) {
            throw new \Exception(__('purchase_returns.cannot_delete_approved'), 422);
        }

        DB::transaction(function () use ($purchaseReturn) {
            $purchaseReturn->items()->delete();
            $purchaseReturn->delete();
        });
    }

    private function validateItemsBelongToPurchase(int $purchaseId, array $items): void
    {
        $itemIds = collect($items)->pluck('purchase_item_id')->unique()->toArray();

        $validCount = PurchaseItem::whereIn('id', $itemIds)
            ->where('purchase_id', $purchaseId)
            ->count();

        if ($validCount !== count($itemIds)) {
            throw new \Exception(__('purchase_returns.invalid_purchase_items'), 422);
        }
    }

}
