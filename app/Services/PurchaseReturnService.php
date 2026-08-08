<?php

namespace App\Services;

use App\Enums\PurchaseReturnStatus;
use App\Models\Batch;
use App\Models\Purchase;
use App\Models\PurchaseItem;
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
    public function list(Request $request, Purchase $purchase): LengthAwarePaginator
    {
        return QueryBuilder::for(PurchaseReturn::class, $request)
            ->where('purchase_id', $purchase->id)
            ->with(['purchase.inventory', 'items.purchaseItem.product'])
            ->allowedFilters(
                AllowedFilter::partial('reference'),
                AllowedFilter::callback('inventory_id', function ($query, $value) {
                    $query->whereHas('purchase', fn ($q) => $q->where('inventory_id', $value));
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

            return $purchaseReturn->fresh()->loadMissing(['purchase', 'items.purchaseItem.product']);
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
                'note'        => $data['note'] ?? $purchaseReturn->note,
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
        return $purchaseReturn->loadMissing(['purchase.inventory', 'items.purchaseItem.product']);
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
                $totalReturnAmount += $returnItem->quantity * $purchaseItem->price;

                $batch = Batch::where('source_id', $purchaseItem->id)
                    ->where('source_type', 'purchase_items')
                    ->first();

                if ($batch) {
                    $oldQuantity = $batch->stock->batches()->sum('current_quantity');
                    $batch->decrement('current_quantity', $returnItem->quantity);

                    $this->inventoryService->returnOut(
                        stockId: $batch->stock_id,
                        inventoryId: $batch->stock->inventory_id,
                        productId: $purchaseItem->product_id,
                        oldQuantity: $oldQuantity,
                        quantity: $returnItem->quantity,
                        source: $purchaseReturn,
                    );
                }
            }

            if ($totalReturnAmount > 0) {
                $wallet = Wallet::findOrFail($walletId);

                $this->walletService->purchaseReturn(
                    wallet: $wallet,
                    amount: $totalReturnAmount,
                    source: $purchaseReturn,
                    note: $purchaseReturn->note,
                );
            }

            $purchaseReturn->update(['status' => PurchaseReturnStatus::COMPLETED]);

            return $purchaseReturn->fresh()->loadMissing(['purchase', 'items.purchaseItem.product']);
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
