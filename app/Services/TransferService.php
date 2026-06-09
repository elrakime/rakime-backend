<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Models\InventoryMovement;
use App\Models\Stock;
use App\Models\Transfer;
use App\Models\TransferItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class TransferService
{
    public function list(Request $request): LengthAwarePaginator
    {
        return QueryBuilder::for(Transfer::class, $request)
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
                AllowedSort::field('transferred_at'),
                AllowedSort::field('received_at'),
                AllowedSort::field('created_at'),
            )
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 15))
            ->appends($request->query());
    }

    public function create(array $data): Transfer
    {
        return DB::transaction(function () use ($data) {
            $transfer = Transfer::create([
                'from_inventory_id' => $data['from_inventory_id'],
                'to_inventory_id'   => $data['to_inventory_id'],
                'note'              => $data['note'] ?? null,
                'transferred_at'    => $data['transferred_at'] ?? now(),
            ]);

            foreach ($data['items'] as $item) {
                TransferItem::create([
                    'transfer_id' => $transfer->id,
                    'stock_id'    => $item['stock_id'],
                    'quantity'    => $item['quantity'],
                ]);
            }

            return $transfer->load(['fromInventory', 'toInventory', 'items.stock.product']);
        });
    }

    public function show(Transfer $transfer): Transfer
    {
        return $transfer->loadMissing(['fromInventory', 'toInventory', 'items.stock.product']);
    }

    public function update(Transfer $transfer, array $data): Transfer
    {
        if ($transfer->received_at) {
            throw new \Exception(__('transfers.cannot_update_received'), 422);
        }

        return DB::transaction(function () use ($transfer, $data) {
            $transfer->update(array_filter([
                'from_inventory_id' => $data['from_inventory_id'] ?? null,
                'to_inventory_id'   => $data['to_inventory_id'] ?? null,
                'note'              => $data['note'] ?? null,
                'transferred_at'    => $data['transferred_at'] ?? null,
            ], fn ($v) => $v !== null));

            if (array_key_exists('items', $data)) {
                $transfer->items()->delete();

                foreach ($data['items'] as $item) {
                    TransferItem::create([
                        'transfer_id' => $transfer->id,
                        'stock_id'    => $item['stock_id'],
                        'quantity'    => $item['quantity'],
                    ]);
                }
            }

            return $transfer->fresh()->loadMissing(['fromInventory', 'toInventory', 'items.stock.product']);
        });
    }

    public function receive(Transfer $transfer): Transfer
    {
        if ($transfer->received_at) {
            return $transfer;
        }

        return DB::transaction(function () use ($transfer) {
            $transfer->update(['received_at' => now()]);

            foreach ($transfer->items as $transferItem) {
                $fromStock = $transferItem->stock;

                // Decrement stock in the source inventory
                if ($fromStock) {
                    $fromBatch = $fromStock->batches()
                        ->where('current_quantity', '>', 0)
                        ->orderBy('purchased_at')
                        ->first();

                    if ($fromBatch) {
                        $fromBatch->decrement('current_quantity', $transferItem->quantity);
                    }

                    InventoryMovement::create([
                        'stock_id'      => $fromStock->id,
                        'batch_id'      => $fromBatch?->id,
                        'inventory_id'  => $transfer->from_inventory_id,
                        'product_id'    => $transferItem->stock->product_id,
                        'moveable_id'   => $transfer->id,
                        'movement_type' => InventoryMovementType::TRANSFER_OUT,
                        'quantity'      => $transferItem->quantity,
                    ]);
                }

                // Increment stock in the destination inventory
                $toStock = Stock::firstOrCreate([
                    'inventory_id' => $transfer->to_inventory_id,
                    'product_id'   => $transferItem->stock->product_id,
                ]);

                $toBatch = $toStock->batches()->create([
                    'source_id'        => $transferItem->id,
                    'source_type'      => 'transfer_item',
                    'purchase_price'   => 0,
                    'initial_quantity' => $transferItem->quantity,
                    'current_quantity' => $transferItem->quantity,
                    'purchased_at'     => $transfer->transferred_at,
                ]);

                InventoryMovement::create([
                    'stock_id'      => $toStock->id,
                    'batch_id'      => $toBatch->id,
                    'inventory_id'  => $transfer->to_inventory_id,
                    'product_id'    => $transferItem->stock->product_id,
                    'moveable_id'   => $transfer->id,
                    'movement_type' => InventoryMovementType::TRANSFER_IN,
                    'quantity'      => $transferItem->quantity,
                ]);
            }

            return $transfer->fresh()->loadMissing(['fromInventory', 'toInventory', 'items.stock.product']);
        });
    }

    public function delete(Transfer $transfer): void
    {
        if ($transfer->received_at) {
            throw new \Exception(__('transfers.cannot_delete_received'), 422);
        }

        $transfer->items()->delete();
        $transfer->delete();
    }
}
