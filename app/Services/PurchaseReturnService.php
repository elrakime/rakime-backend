<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Enums\PurchaseReturnStatus;
use App\Models\Batch;
use App\Models\InventoryMovement;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseRefund;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Wallet;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class PurchaseReturnService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly WalletService $walletService,
    ) {}

    public function list(Request $request): LengthAwarePaginator
    {
        return QueryBuilder::for(PurchaseReturn::class, $request)
            ->with(['purchase.inventory', 'purchase.branch', 'purchase.returns.items.purchaseItem', 'items.purchaseItem.product', 'items.purchaseItem.returnItems.purchaseReturn'])
            ->allowedFilters(
                AllowedFilter::partial('reference'),
                AllowedFilter::exact('purchase_id'),
                AllowedFilter::callback('inventory_id', function ($query, $value) {
                    $query->whereHas('purchase', fn ($q) => $q->where('inventory_id', $value));
                }),
                AllowedFilter::callback('branch_id', function ($query, $value) {
                    $query->whereHas('purchase', fn ($q) => $q->where('branch_id', $value));
                }),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('reference', 'like', "%{$value}%");
                    });
                }),
            )
            ->allowedSorts(
                AllowedSort::field('reference'),
                AllowedSort::field('created_at'),
            )
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 15))
            ->appends($request->query());
    }

    public function create(Purchase $purchase, array $data): PurchaseReturn
    {
        $this->validateItemsBelongToPurchase($purchase->id, $data['items']);
        $this->validateReturnQuantities($data['items']);

        return DB::transaction(function () use ($purchase, $data) {
            $purchaseReturn = PurchaseReturn::create([
                'purchase_id' => $purchase->id,
                'note'        => $data['note'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                PurchaseReturnItem::create([
                    'purchase_return_id' => $purchaseReturn->id,
                    'purchase_item_id'   => $item['purchase_item_id'],
                    'quantity'           => $item['quantity'],
                    'reason'             => $item['reason'] ?? null,
                ]);
            }

            return $purchaseReturn->fresh()->loadMissing(['purchase', 'purchase.returns.items.purchaseItem', 'items.purchaseItem.product', 'items.purchaseItem.returnItems.purchaseReturn']);
        });
    }

    public function update(PurchaseReturn $purchaseReturn, array $data): PurchaseReturn
    {
        if ($purchaseReturn->status === PurchaseReturnStatus::COMPLETED) {
            throw new Exception(__('purchase_returns.cannot_update_approved'), 422);
        }

        if (isset($data['items'])) {
            $purchaseReturn->load(['purchase', 'items']);
            $this->validateItemsBelongToPurchase($purchaseReturn->purchase_id, $data['items']);
            $this->validateReturnQuantities($data['items']);
        }

        return DB::transaction(function () use ($purchaseReturn, $data) {
            $purchaseReturn->update([
                'note' => $data['note'] ?? $purchaseReturn->note,
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

            return $purchaseReturn->fresh()->loadMissing(['purchase', 'purchase.returns.items.purchaseItem', 'items.purchaseItem.product', 'items.purchaseItem.returnItems.purchaseReturn']);
        });
    }

    public function show(PurchaseReturn $purchaseReturn): PurchaseReturn
    {
        return $purchaseReturn->loadMissing(['purchase.inventory', 'purchase.returns.items.purchaseItem', 'items.purchaseItem.product', 'items.purchaseItem.returnItems.purchaseReturn']);
    }

    public function approve(PurchaseReturn $purchaseReturn, int $walletId): PurchaseReturn
    {
        $purchaseReturn->load('items.purchaseItem');

        foreach ($purchaseReturn->items as $returnItem) {
            if ($returnItem->quantity > $returnItem->purchaseItem->net_quantity) {
                throw new Exception(__('purchase_returns.quantity_exceeds_available'), 422);
            }
        }

        return DB::transaction(function () use ($purchaseReturn, $walletId) {
            $purchaseReturn->load('items.purchaseItem');

            $totalReturnAmount = 0;

            foreach ($purchaseReturn->items as $returnItem) {
                $purchaseItem = $returnItem->purchaseItem;

                $sourceBatch = Batch::where('source_id', $purchaseItem->id)
                    ->where('source_type', PurchaseItem::class)
                    ->first();

                if (!$sourceBatch) {
                    throw new Exception(__('purchase_returns.source_batch_not_found'), 422);
                }

                $totalReturnAmount += $returnItem->quantity * $purchaseItem->price;

                $stock = $sourceBatch->stock;
                $oldQuantity = $stock->batches()->sum('current_quantity');

                $allocations = $this->reverseReceiveAllocation(
                    $sourceBatch->id,
                    $purchaseReturn->purchase_id,
                    $returnItem->quantity,
                );

                $this->inventoryService->returnOut(
                    stockId: $stock->id,
                    inventoryId: $stock->inventory_id,
                    productId: $purchaseItem->product_id,
                    oldQuantity: $oldQuantity,
                    quantity: $returnItem->quantity,
                    source: $purchaseReturn,
                    allocations: $allocations,
                );
            }

            // Refund only the portion of paid_amount that is now overpaid
            // after this return reduces the amount still owed.
            $purchase = $purchaseReturn->purchase()->first();

            $newNetAmount = $purchase->net_amount - $totalReturnAmount;
            $refundAmount = max(0, $purchase->paid_amount - $newNetAmount);

            if ($refundAmount > 0) {
                $wallet = Wallet::findOrFail($walletId);

                $refund = PurchaseRefund::create([
                    'purchase_id'        => $purchaseReturn->purchase_id,
                    'purchase_return_id' => $purchaseReturn->id,
                    'amount'             => $refundAmount,
                ]);

                $this->walletService->purchaseReturn(
                    wallet: $wallet,
                    amount: $refundAmount,
                    source: $refund,
                    note: $purchaseReturn->note,
                );
            }

            $purchaseReturn->update(['status' => PurchaseReturnStatus::COMPLETED]);

            $purchase->recalculateAmounts();

            return $purchaseReturn->fresh()->loadMissing(['purchase', 'purchase.returns.items.purchaseItem', 'items.purchaseItem.product', 'items.purchaseItem.returnItems.purchaseReturn']);
        });
    }

    public function delete(PurchaseReturn $purchaseReturn): void
    {
        if ($purchaseReturn->status === PurchaseReturnStatus::COMPLETED) {
            throw new Exception(__('purchase_returns.cannot_delete_approved'), 422);
        }

        DB::transaction(function () use ($purchaseReturn) {
            $purchaseReturn->items()->delete();
            $purchaseReturn->delete();
        });
    }

    public function cancel(PurchaseReturn $purchaseReturn): PurchaseReturn
    {
        if ($purchaseReturn->status !== PurchaseReturnStatus::PENDING) {
            throw new Exception(__('purchase_returns.not_pending'), 422);
        }

        $purchaseReturn->update(['status' => PurchaseReturnStatus::CANCELED]);

        return $purchaseReturn->refresh()->loadMissing(['purchase', 'purchase.returns.items.purchaseItem', 'items.purchaseItem.product', 'items.purchaseItem.returnItems.purchaseReturn']);
    }

    /**
     * Reverse the batch's single RECEIVE allocation, crediting the return
     * against it. A batch is created by exactly one receive event tied to
     * exactly one PurchaseItem, so there is only ever one allocation to
     * reverse against here - no LIFO/multi-layer walk needed (unlike sales).
     */
    private function reverseReceiveAllocation(int $batchId, int $purchaseId, int $returnQty): array
    {
        $movement = InventoryMovement::query()
            ->where('movement_type', InventoryMovementType::RECEIVE->value)
            ->whereHas('allocations', fn ($q) => $q->where('batch_id', $batchId))
            ->with(['allocations' => fn ($q) => $q->where('batch_id', $batchId)])
            ->first();

        $allocation = $movement?->allocations->first();

        if (!$allocation) {
            throw new Exception(__('purchase_returns.no_receive_allocation'), 422);
        }

        $received = $allocation->quantity;
        $alreadyReturned = $this->purchaseAlreadyReturnedForBatch($batchId, $purchaseId);
        $available = $received - $alreadyReturned;

        if ($returnQty > $available) {
            throw new Exception(__('purchase_returns.insufficient_allocations'), 422);
        }

        $batch = Batch::whereKey($batchId)->lockForUpdate()->first();

        if (!$batch) {
            throw new Exception(__('purchase_returns.batch_not_found'), 422);
        }

        if ($returnQty > $batch->current_quantity) {
            // Distinct from insufficient_allocations: the ledger allows it,
            // but this stock has since left the building (sold/transferred).
            throw new Exception(__('purchase_returns.stock_no_longer_available'), 422);
        }

        $batch->decrement('current_quantity', $returnQty);

        return [[
            'batch_id'       => $batchId,
            'quantity'       => -$returnQty,
            'purchase_price' => $allocation->purchase_price,
        ]];
    }

    private function purchaseAlreadyReturnedForBatch(int $batchId, int $purchaseId): int
    {
        $returnIds = PurchaseReturn::query()
            ->where('purchase_id', $purchaseId)
            ->where('status', PurchaseReturnStatus::COMPLETED->value)
            ->pluck('id');

        if ($returnIds->isEmpty()) {
            return 0;
        }

        return InventoryMovement::query()
            ->where('movement_type', InventoryMovementType::RETURN->value)
            ->where('source_type', PurchaseReturn::class)
            ->whereIn('source_id', $returnIds)
            ->whereHas('allocations', fn ($q) => $q->where('batch_id', $batchId))
            ->with(['allocations' => fn ($q) => $q->where('batch_id', $batchId)])
            ->get()
            ->flatMap->allocations
            ->sum(fn ($allocation) => abs($allocation->quantity));
    }

    private function validateItemsBelongToPurchase(int $purchaseId, array $items): void
    {
        $itemIds = collect($items)->pluck('purchase_item_id')->unique()->toArray();

        $validCount = PurchaseItem::whereIn('id', $itemIds)
            ->where('purchase_id', $purchaseId)
            ->count();

        if ($validCount !== count($itemIds)) {
            throw new Exception(__('purchase_returns.invalid_purchase_items'), 422);
        }
    }

    private function validateReturnQuantities(array $items): void
    {
        $purchaseItems = PurchaseItem::with(['returnItems' => function ($q) {
            $q->whereHas('purchaseReturn', fn ($q2) => $q2->where('status', 'completed'));
        }])->findMany(collect($items)->pluck('purchase_item_id'));

        foreach ($items as $item) {
            $purchaseItem = $purchaseItems->find($item['purchase_item_id']);

            $available = $purchaseItem->quantity - $purchaseItem->returnItems->sum('quantity');

            if ($item['quantity'] > $available) {
                throw new Exception(__('purchase_returns.quantity_exceeds_available'), 422);
            }
        }
    }
}