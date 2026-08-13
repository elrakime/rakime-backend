<?php

namespace App\Services;

use App\Enums\InventoryTransferStatus;
use App\Models\InventoryTransfer;
use App\Models\InventoryTransferItem;
use App\Models\Restock;
use App\Models\Stock;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class InventoryTransferService
{
    public function __construct(private readonly InventoryService $inventoryService) {}
    public function list(Request $request): LengthAwarePaginator
    {
        return QueryBuilder::for(InventoryTransfer::class, $request)
            ->with(['fromInventory', 'toInventory', 'items.stock.product'])
            ->allowedFilters(
                AllowedFilter::exact('from_inventory_id'),
                AllowedFilter::exact('to_inventory_id'),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('note', 'like', "%{$value}%");
                    });
                }),
            )
            ->allowedSorts(
                AllowedSort::field('created_at'),
            )
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 15))
            ->appends($request->query());
    }

    public function create(array $data): InventoryTransfer
    {
        $this->validateStocksBelongToInventory($data['from_inventory_id'], $data['items']);

        return DB::transaction(function () use ($data) {
            $transfer = InventoryTransfer::create([
                'from_inventory_id' => $data['from_inventory_id'],
                'to_inventory_id'   => $data['to_inventory_id'],
                'note'              => $data['note'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                InventoryTransferItem::create([
                    'inventory_transfer_id' => $transfer->id,
                    'stock_id'              => $item['stock_id'],
                    'quantity'              => $item['quantity'],
                ]);
            }

            return $transfer->fresh()->load(['fromInventory', 'toInventory', 'items.stock.product']);
        });
    }

    public function show(InventoryTransfer $transfer): InventoryTransfer
    {
        return $transfer->loadMissing(['fromInventory', 'toInventory', 'items.stock.product']);
    }

    public function update(InventoryTransfer $transfer, array $data): InventoryTransfer
    {
        return DB::transaction(function () use ($transfer, $data) {
            $transfer->update(array_filter([
                'from_inventory_id' => $data['from_inventory_id'] ?? null,
                'to_inventory_id'   => $data['to_inventory_id'] ?? null,
                'note'              => $data['note'] ?? null,
            ], fn ($v) => $v !== null));

            if (array_key_exists('items', $data)) {

                $fromInventoryId = $transfer->from_inventory_id;
                $this->validateStocksBelongToInventory($fromInventoryId, $data['items']);

                $transfer->items()->delete();

                foreach ($data['items'] as $item) {
                    InventoryTransferItem::create([
                        'inventory_transfer_id' => $transfer->id,
                        'stock_id'              => $item['stock_id'],
                        'quantity'              => $item['quantity'],
                    ]);
                }
            }

            return $transfer->fresh()->loadMissing(['fromInventory', 'toInventory', 'items.stock.product']);
        });
    }

    public function dispatch(InventoryTransfer $transfer): InventoryTransfer
    {
        return DB::transaction(function () use ($transfer) {
            $transfer->loadMissing('items.stock');

            foreach ($transfer->items as $transferItem) {
                $fromStock = $transferItem->stock;

                if ($fromStock) {
                    $remaining = $transferItem->quantity;

                    $batches = $fromStock->batches()
                        ->where('current_quantity', '>', 0)
                        ->orderBy('created_at')
                        ->get();

                    $oldQuantity = $batches->sum('current_quantity');

                    $allocations = [];

                    foreach ($batches as $batch) {
                        $deduct = min($remaining, $batch->current_quantity);
                        $batch->decrement('current_quantity', $deduct);

                        $allocations[] = [
                            'batch_id'       => $batch->id,
                            'quantity'       => -$deduct,
                            'purchase_price' => $batch->purchase_price,
                        ];

                        $remaining -= $deduct;
                        if ($remaining <= 0) {
                            break;
                        }
                    }

                    $this->inventoryService->transferOut(
                        stockId: $fromStock->id,
                        inventoryId: $transfer->from_inventory_id,
                        productId: $transferItem->stock->product_id,
                        oldQuantity: $oldQuantity,
                        quantity: $transferItem->quantity,
                        source: $transfer,
                        allocations: $allocations,
                    );
                }
            }

            $transfer->update(['status' => InventoryTransferStatus::DISPATCHED]);

            return $transfer->fresh()->loadMissing(['fromInventory', 'toInventory', 'items.stock.product']);
        });
    }

    public function receive(InventoryTransfer $transfer): InventoryTransfer
    {
        return DB::transaction(function () use ($transfer) {
            $transfer->loadMissing('items.stock');

            // If this transfer was created from a restock, load the restock to update fulfilled quantities
            $restock = Restock::where('fulfilled_with_id', $transfer->id)
                ->where('fulfilled_with_type', InventoryTransfer::class)
                ->first();

            foreach ($transfer->items as $transferItem) {
                $toStock = Stock::firstOrCreate([
                    'inventory_id' => $transfer->to_inventory_id,
                    'product_id'   => $transferItem->stock->product_id,
                ]);

                $oldQuantity = $toStock->batches()->sum('current_quantity');

                $toBatch = $toStock->batches()->create([
                    'source_id'        => $transferItem->id,
                    'source_type'      => InventoryTransferItem::class,
                    'purchase_price'   => 0,
                    'initial_quantity' => $transferItem->quantity,
                    'current_quantity' => $transferItem->quantity,
                ]);

                $this->inventoryService->transferIn(
                    stockId: $toStock->id,
                    inventoryId: $transfer->to_inventory_id,
                    productId: $transferItem->stock->product_id,
                    oldQuantity: $oldQuantity,
                    quantity: $transferItem->quantity,
                    source: $transfer,
                    allocations: [
                        [
                            'batch_id'       => $toBatch->id,
                            'quantity'       => $transferItem->quantity,
                            'purchase_price' => $toBatch->purchase_price,
                        ],
                    ],
                );

                // Update restock fulfilled_quantity if linked
                if ($restock) {
                    $restockItem = $restock->items()->where('product_id', $transferItem->stock->product_id)->first();
                    if ($restockItem) {
                        $restockItem->increment('fulfilled_quantity', $transferItem->quantity);
                    }
                }
            }

            $transfer->update(['status' => InventoryTransferStatus::RECEIVED]);

            return $transfer->fresh()->loadMissing(['fromInventory', 'toInventory', 'items.stock.product']);
        });
    }

    public function cancel(InventoryTransfer $transfer): InventoryTransfer
    {
        return DB::transaction(function () use ($transfer) {
            if ($transfer->status === InventoryTransferStatus::DISPATCHED) {
                $transfer->loadMissing('items.stock');

                foreach ($transfer->items as $transferItem) {
                    $fromStock = $transferItem->stock;

                    if ($fromStock) {
                        $oldQuantity = $fromStock->batches()->sum('current_quantity');

                        $creditBatch = $fromStock->batches()->create([
                            'source_id'        => $transferItem->id,
                            'source_type'      => InventoryTransferItem::class,
                            'purchase_price'   => 0,
                            'initial_quantity' => $transferItem->quantity,
                            'current_quantity' => $transferItem->quantity,
                        ]);

                        $this->inventoryService->transferCancel(
                            stockId: $fromStock->id,
                            inventoryId: $transfer->from_inventory_id,
                            productId: $transferItem->stock->product_id,
                            oldQuantity: $oldQuantity,
                            quantity: $transferItem->quantity,
                            source: $transfer,
                            allocations: [
                                [
                                    'batch_id'       => $creditBatch->id,
                                    'quantity'       => $transferItem->quantity,
                                    'purchase_price' => $creditBatch->purchase_price,
                                ],
                            ],
                        );
                    }
                }
            }

            $transfer->update(['status' => InventoryTransferStatus::CANCELED]);

            return $transfer->fresh()->loadMissing(['fromInventory', 'toInventory', 'items.stock.product']);
        });
    }

    public function delete(InventoryTransfer $transfer): void
    {
        $transfer->items()->delete();
        $transfer->delete();
    }

    private function validateStocksBelongToInventory(int $inventoryId, array $items): void
    {
        foreach ($items as $index => $item) {
            if (!isset($item['stock_id'])) {
                continue;
            }

            $exists = Stock::where('id', $item['stock_id'])
                ->where('inventory_id', $inventoryId)
                ->exists();

            if (!$exists) {
                throw new Exception(
                    "Stock #{$item['stock_id']} does not belong to inventory #{$inventoryId}.",
                    422,
                );
            }
        }
    }
}
