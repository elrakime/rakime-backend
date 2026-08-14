<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Enums\SaleReturnStatus;
use App\Models\Batch;
use App\Models\InventoryMovement;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\Wallet;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class SaleReturnService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly WalletService $walletService,
    ) {}

    public function list(Request $request, Sale $sale): LengthAwarePaginator
    {
        return QueryBuilder::for(SaleReturn::class, $request)
            ->where('sale_id', $sale->id)
            ->with(['sale.branch', 'items.saleItem.product', 'items.saleItem.stock', 'items.saleItem.returnItems.saleReturn'])
            ->allowedFilters(
                AllowedFilter::partial('reference'),
                AllowedFilter::callback('branch_id', function ($query, $value) {
                    $query->whereHas('sale', fn ($q) => $q->where('branch_id', $value));
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

    public function create(Sale $sale, array $data): SaleReturn
    {
        $this->validateItemsBelongToSale($sale->id, $data['items']);
        $this->validateReturnQuantities($data['items']);

        return DB::transaction(function () use ($sale, $data) {
            $saleReturn = SaleReturn::create([
                'sale_id' => $sale->id,
                'note'    => $data['note'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                SaleReturnItem::create([
                    'sale_return_id' => $saleReturn->id,
                    'sale_item_id'   => $item['sale_item_id'],
                    'quantity'       => $item['quantity'],
                    'reason'         => $item['reason'] ?? null,
                ]);
            }

            return $saleReturn->fresh()->loadMissing(['sale', 'items.saleItem.product', 'items.saleItem.stock', 'items.saleItem.returnItems.saleReturn']);
        });
    }

    public function update(SaleReturn $saleReturn, array $data): SaleReturn
    {
        if ($saleReturn->status === SaleReturnStatus::COMPLETED) {
            throw new Exception(__('sale_returns.cannot_update_approved'), 422);
        }

        if (isset($data['items'])) {
            $saleReturn->load(['sale', 'items']);
            $this->validateItemsBelongToSale($saleReturn->sale_id, $data['items']);
            $this->validateReturnQuantities($data['items']);
        }

        return DB::transaction(function () use ($saleReturn, $data) {
            $saleReturn->update([
                'note' => $data['note'] ?? $saleReturn->note,
            ]);

            if (isset($data['items'])) {
                $saleReturn->items()->delete();

                foreach ($data['items'] as $item) {
                    SaleReturnItem::create([
                        'sale_return_id' => $saleReturn->id,
                        'sale_item_id'   => $item['sale_item_id'],
                        'quantity'       => $item['quantity'],
                        'reason'         => $item['reason'] ?? null,
                    ]);
                }
            }

            return $saleReturn->fresh()->loadMissing(['sale', 'items.saleItem.product', 'items.saleItem.stock', 'items.saleItem.returnItems.saleReturn']);
        });
    }

    public function show(SaleReturn $saleReturn): SaleReturn
    {
        return $saleReturn->loadMissing(['sale.branch', 'items.saleItem.product', 'items.saleItem.stock', 'items.saleItem.returnItems.saleReturn']);
    }

    public function approve(SaleReturn $saleReturn, int $walletId): SaleReturn
    {
        $saleReturn->load('items.saleItem');

        foreach ($saleReturn->items as $returnItem) {
            if ($returnItem->quantity > $returnItem->saleItem->net_quantity) {
                throw new Exception(__('sale_returns.quantity_exceeds_available'), 422);
            }
        }

        return DB::transaction(function () use ($saleReturn, $walletId) {
            $saleReturn->load('items.saleItem.stock');

            $totalReturnAmount = 0;

            foreach ($saleReturn->items as $returnItem) {
                $saleItem = $returnItem->saleItem;

                // sale_items.stock_id is not nullable - a missing stock here
                // means the data is inconsistent, never silently skip it.
                $stock = $saleItem->stock;
                if (!$stock) {
                    throw new Exception(__('sale_returns.stock_not_found'), 422);
                }

                // Lock the sale item's row so a concurrent return approval
                // against the same sale item can't read the same
                // pre-return ledger state and double-credit batches.
                SaleItem::whereKey($saleItem->id)->lockForUpdate()->first();

                $totalReturnAmount += $returnItem->quantity * $saleItem->price;

                $oldQuantity = $stock->batches()->sum('current_quantity');

                $allocations = $this->reverseSaleAllocationsLifo(
                    $saleItem->sale_id,
                    $stock->id,
                    $returnItem->quantity,
                );

                $this->inventoryService->saleReturn(
                    stockId: $stock->id,
                    inventoryId: $stock->inventory_id,
                    productId: $saleItem->product_id,
                    oldQuantity: $oldQuantity,
                    quantity: $returnItem->quantity,
                    source: $saleReturn,
                    allocations: $allocations,
                );
            }

            if ($totalReturnAmount > 0) {
                $wallet = Wallet::findOrFail($walletId);

                $this->walletService->saleReturn(
                    wallet: $wallet,
                    amount: $totalReturnAmount,
                    source: $saleReturn,
                    note: $saleReturn->note,
                );
            }

            $saleReturn->update(['status' => SaleReturnStatus::COMPLETED]);

            return $saleReturn->fresh()->loadMissing(['sale', 'items.saleItem.product', 'items.saleItem.stock', 'items.saleItem.returnItems.saleReturn']);
        });
    }

    public function delete(SaleReturn $saleReturn): void
    {
        if ($saleReturn->status === SaleReturnStatus::COMPLETED) {
            throw new Exception(__('sale_returns.cannot_delete_approved'), 422);
        }

        DB::transaction(function () use ($saleReturn) {
            $saleReturn->items()->delete();
            $saleReturn->delete();
        });
    }

    public function cancel(SaleReturn $saleReturn): SaleReturn
    {
        if ($saleReturn->status !== SaleReturnStatus::PENDING) {
            throw new Exception(__('sale_returns.not_pending'), 422);
        }

        $saleReturn->update(['status' => SaleReturnStatus::CANCELED]);

        return $saleReturn->refresh()->loadMissing(['sale', 'items.saleItem.product', 'items.saleItem.stock', 'items.saleItem.returnItems.saleReturn']);
    }

    /**
     * Reverse the sale's SALE / SALE_UPDATE allocation ledger in LIFO order,
     * crediting back the exact batches that were originally deducted.
     */
    private function reverseSaleAllocationsLifo(int $saleId, int $stockId, int $returnQty): array
    {
        $layers = [];

        InventoryMovement::query()
            ->where('stock_id', $stockId)
            ->where('source_type', Sale::class)
            ->where('source_id', $saleId)
            ->whereIn('movement_type', [
                InventoryMovementType::SALE->value,
                InventoryMovementType::SALE_UPDATE->value,
            ])
            ->orderByDesc('id')
            ->with(['allocations' => fn ($q) => $q->orderByDesc('id')])
            ->get()
            ->each(function (InventoryMovement $movement) use (&$layers) {
                foreach ($movement->allocations as $allocation) {
                    if ($allocation->quantity < 0) {
                        $layers[] = $allocation;
                    }
                }
            });

        $alreadyReturned = $this->saleAlreadyReturnedByBatch($saleId, $stockId);

        $remaining = $returnQty;
        $allocations = [];

        foreach ($layers as $layer) {
            $layerBatchId = $layer->batch_id;
            $allocated = abs($layer->quantity);

            $already = $alreadyReturned[$layerBatchId] ?? 0;
            $consumed = min($already, $allocated);
            $alreadyReturned[$layerBatchId] = $already - $consumed;

            $available = $allocated - $consumed;
            if ($available <= 0) {
                continue;
            }

            $credit = min($remaining, $available);

            Batch::whereKey($layerBatchId)->increment('current_quantity', $credit);

            $allocations[] = [
                'batch_id'       => $layerBatchId,
                'quantity'       => $credit,
                'purchase_price' => $layer->purchase_price,
            ];

            $remaining -= $credit;
            if ($remaining <= 0) {
                break;
            }
        }

        if ($remaining > 0) {
            throw new Exception(__('sale_returns.insufficient_allocations'), 422);
        }

        return $allocations;
    }

    private function saleAlreadyReturnedByBatch(int $saleId, int $stockId): array
    {
        $returnIds = SaleReturn::query()
            ->where('sale_id', $saleId)
            ->where('status', SaleReturnStatus::COMPLETED->value)
            ->pluck('id');

        if ($returnIds->isEmpty()) {
            return [];
        }

        $alreadyReturned = [];

        InventoryMovement::query()
            ->where('stock_id', $stockId)
            ->where('source_type', SaleReturn::class)
            ->whereIn('source_id', $returnIds)
            ->with('allocations')
            ->get()
            ->each(function (InventoryMovement $movement) use (&$alreadyReturned) {
                foreach ($movement->allocations as $allocation) {
                    if ($allocation->quantity > 0) {
                        $alreadyReturned[$allocation->batch_id] = ($alreadyReturned[$allocation->batch_id] ?? 0) + $allocation->quantity;
                    }
                }
            });

        return $alreadyReturned;
    }

    private function validateItemsBelongToSale(int $saleId, array $items): void
    {
        $itemIds = collect($items)->pluck('sale_item_id')->unique()->toArray();

        $validCount = SaleItem::whereIn('id', $itemIds)
            ->where('sale_id', $saleId)
            ->count();

        if ($validCount !== count($itemIds)) {
            throw new Exception(__('sale_returns.invalid_sale_items'), 422);
        }
    }

    private function validateReturnQuantities(array $items): void
    {
        $saleItems = SaleItem::with(['returnItems' => function ($q) {
            $q->whereHas('saleReturn', fn ($q2) => $q2->where('status', 'completed'));
        }])->findMany(collect($items)->pluck('sale_item_id'));

        foreach ($items as $item) {
            $saleItem = $saleItems->find($item['sale_item_id']);

            $available = $saleItem->quantity - $saleItem->returnItems->sum('quantity');

            if ($item['quantity'] > $available) {
                throw new Exception(__('sale_returns.quantity_exceeds_available'), 422);
            }
        }
    }
}