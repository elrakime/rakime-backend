<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Enums\RestockStatus;
use App\Models\Batch;
use App\Models\InventoryMovement;
use App\Models\Purchase;
use App\Models\Restock;
use App\Models\RestockItem;
use App\Models\Stock;
use App\Models\InventoryTransfer;
use App\Traits\ScopesByUserBranches;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class RestockService
{
    use ScopesByUserBranches;
    public function list(Request $request): LengthAwarePaginator
    {
        $query = Restock::query();

        $this->scopeByUserBranches($query);

        return QueryBuilder::for($query, $request)
            ->with(['user', 'branch', 'items.product', 'fulfilledWith'])
            ->allowedFilters(
                AllowedFilter::exact('branch_id'),
                AllowedFilter::exact('status'),
                AllowedFilter::partial('reference'),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('reference', 'like', "%{$value}%");
                    });
                }),
            )
            ->allowedSorts(
                AllowedSort::field('reference'),
                AllowedSort::field('status'),
                AllowedSort::field('created_at'),
            )
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 15))
            ->appends($request->query());
    }

    public function create(array $data): Restock
    {
        return DB::transaction(function () use ($data) {
            $restock = Restock::create([
                'user_id'   => $data['user_id'] ?? auth()->id(),
                'branch_id' => $data['branch_id'],
                'status'    => RestockStatus::DRAFT,
                'note'      => $data['note'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                RestockItem::create([
                    'restock_id'   => $restock->id,
                    'product_id'         => $item['product_id'],
                    'requested_quantity' => $item['requested_quantity'],
                    'fulfilled_quantity' => 0,
                ]);
            }

            return $restock->load(['user', 'branch', 'items.product']);
        });
    }

    public function show(Restock $restock): Restock
    {
        return $restock->loadMissing(['user', 'branch', 'items.product', 'fulfilledWith']);
    }

    public function update(Restock $restock, array $data): Restock
    {
        if ($restock->status !== RestockStatus::DRAFT) {
            throw new \Exception(__('restocks.not_draft'), 422);
        }

        return DB::transaction(function () use ($restock, $data) {
            $restock->update([
                'branch_id' => $data['branch_id'] ?? $restock->branch_id,
                'note'      => $data['note'] ?? $restock->note,
            ]);

            if (isset($data['items'])) {
                $restock->items()->delete();

                foreach ($data['items'] as $item) {
                    RestockItem::create([
                        'restock_id'   => $restock->id,
                        'product_id'         => $item['product_id'],
                        'requested_quantity' => $item['requested_quantity'],
                        'fulfilled_quantity' => 0,
                    ]);
                }
            }

            return $restock->fresh()->loadMissing(['user', 'branch', 'items.product']);
        });
    }

    public function delete(Restock $restock): void
    {
        if ($restock->status !== RestockStatus::DRAFT) {
            throw new \Exception(__('restocks.not_draft'), 422);
        }

        DB::transaction(function () use ($restock) {
            $restock->items()->delete();
            $restock->delete();
        });
    }

    public function submit(Restock $restock): Restock
    {
        if ($restock->status !== RestockStatus::DRAFT) {
            throw new \Exception(__('restocks.not_draft'), 422);
        }

        $restock->update(['status' => RestockStatus::SUBMITTED]);

        return $restock->fresh()->loadMissing(['user', 'branch', 'items.product']);
    }

    public function cancel(Restock $restock): Restock
    {
        if (!in_array($restock->status, [RestockStatus::DRAFT, RestockStatus::SUBMITTED])) {
            throw new \Exception(__('restocks.cannot_cancel'), 422);
        }

        $restock->update(['status' => RestockStatus::CANCELLED]);

        return $restock->fresh()->loadMissing(['user', 'branch', 'items.product']);
    }

    public function fulfill(Restock $restock, array $data): Restock
    {
        if ($restock->status !== RestockStatus::SUBMITTED) {
            throw new \Exception(__('restocks.must_be_submitted'), 422);
        }

        $type = $data['type'];

        return DB::transaction(function () use ($restock, $data, $type) {
            $fulfilledWith = match ($type) {
                'purchase' => $this->fulfillViaPurchase($restock, $data),
                'transfer' => $this->fulfillViaTransfer($restock, $data),
                'none'     => $this->fulfillNoAction($restock),
                default    => throw new \Exception(__('restocks.invalid_fulfill_type'), 422),
            };

            $restock->update([
                'status'             => RestockStatus::FULFILLED,
                'fulfilled_at'       => now(),
                'fulfilled_with_id'  => $fulfilledWith?->id,
                'fulfilled_with_type' => $fulfilledWith ? get_class($fulfilledWith) : null,
            ]);

            return $restock->fresh()->loadMissing(['user', 'branch', 'items.product', 'fulfilledWith']);
        });
    }

    private function fulfillViaPurchase(Restock $restock, array $data): Purchase
    {
        $restock->loadMissing('branch');

        // Find the inventory belonging to the restock's branch
        $inventory = \App\Models\Inventory::where('branch_id', $restock->branch_id)->first();

        if (!$inventory) {
            throw new \Exception(__('restocks.no_inventory_for_branch'), 422);
        }

        // Create a new purchase from the supplier (prices default to 0 for restock fulfillment)
        $purchase = Purchase::create([
            'supplier_id'  => $data['supplier_id'],
            'status'       => \App\Enums\PurchaseStatus::RECEIVED,
            'total_amount' => 0,
            'paid_amount'  => 0,
            'note'         => $data['note'] ?? null,
            'purchased_at' => now(),
            'received_at'  => now(),
        ]);

        foreach ($restock->items as $restockItem) {
            $quantity = $restockItem->requested_quantity;
            $price = 0;

            $purchaseItem = $purchase->items()->create([
                'product_id' => $restockItem->product_id,
                'quantity'   => $quantity,
                'price'      => $price,
            ]);

            // Create/reuse stock in the branch inventory
            $stock = Stock::firstOrCreate([
                'inventory_id' => $inventory->id,
                'product_id'   => $restockItem->product_id,
            ]);

            // Create batch for this purchase item
            $batch = $stock->batches()->create([
                'source_id'        => $purchaseItem->id,
                'source_type'      => 'purchase_items',
                'purchase_price'   => $price,
                'initial_quantity' => $quantity,
                'current_quantity' => $quantity,
                'purchased_at'     => now(),
            ]);

            // Record inventory movement for receiving the purchase
            InventoryMovement::create([
                'stock_id'      => $stock->id,
                'batch_id'      => $batch->id,
                'inventory_id'  => $inventory->id,
                'product_id'    => $restockItem->product_id,
                'source_id'   => $purchase->id,
                'movement_type' => InventoryMovementType::RECEIVE,
                'quantity'      => $quantity,
            ]);

            // Record inventory movement for the restock fulfillment
            InventoryMovement::create([
                'stock_id'      => $stock->id,
                'batch_id'      => $batch->id,
                'inventory_id'  => $inventory->id,
                'product_id'    => $restockItem->product_id,
                'source_id'   => $restock->id,
                'movement_type' => InventoryMovementType::RESTOCK_RECEIVED,
                'quantity'      => $quantity,
            ]);

            $restockItem->update([
                'fulfilled_quantity' => $quantity,
            ]);
        }

        return $purchase;
    }

    private function fulfillViaTransfer(Restock $restock, array $data): InventoryTransfer
    {
        $restock->loadMissing('branch');

        // Find the inventory belonging to the restock's branch (destination)
        $toInventory = \App\Models\Inventory::where('branch_id', $restock->branch_id)->first();

        if (!$toInventory) {
            throw new \Exception(__('restocks.no_inventory_for_branch'), 422);
        }

        $fromInventoryId = $data['from_inventory_id'];

        // Create a new transfer from the source inventory to the branch's inventory
        $transfer = InventoryTransfer::create([
            'from_inventory_id' => $fromInventoryId,
            'to_inventory_id'   => $toInventory->id,
            'performed_by'      => auth()->id(),
            'note'              => $data['note'] ?? null,
            'transferred_at'    => now(),
            'received_at'       => now(),
        ]);

        foreach ($restock->items as $restockItem) {
            $quantity = $restockItem->requested_quantity;

            // Find stock in the source inventory to deduct from
            $fromStock = Stock::where('inventory_id', $fromInventoryId)
                ->where('product_id', $restockItem->product_id)
                ->first();

            // Create transfer item (stock_id references the source stock)
            $transferItem = $transfer->items()->create([
                'stock_id' => $fromStock?->id,
                'quantity' => $quantity,
            ]);

            // Decrement from source inventory if stock exists
            if ($fromStock) {
                $fromBatch = $fromStock->batches()
                    ->where('current_quantity', '>', 0)
                    ->orderBy('purchased_at')
                    ->first();

                if ($fromBatch) {
                    $fromBatch->decrement('current_quantity', $quantity);
                }

                InventoryMovement::create([
                    'stock_id'      => $fromStock->id,
                    'batch_id'      => $fromBatch?->id,
                    'inventory_id'  => $fromInventoryId,
                    'product_id'    => $restockItem->product_id,
                    'source_id'   => $transfer->id,
                    'movement_type' => InventoryMovementType::TRANSFER_OUT,
                    'quantity'      => $quantity,
                ]);
            }

            // Create/reuse stock in the destination (branch) inventory
            $toStock = Stock::firstOrCreate([
                'inventory_id' => $toInventory->id,
                'product_id'   => $restockItem->product_id,
            ]);

            // Create batch for the received transfer item
            $toBatch = $toStock->batches()->create([
                'source_id'        => $transferItem->id,
                'source_type'      => 'inventory_transfer_item',
                'purchase_price'   => 0,
                'initial_quantity' => $quantity,
                'current_quantity' => $quantity,
                'purchased_at'     => now(),
            ]);

            // Record transfer-in movement
            InventoryMovement::create([
                'stock_id'      => $toStock->id,
                'batch_id'      => $toBatch->id,
                'inventory_id'  => $toInventory->id,
                'product_id'    => $restockItem->product_id,
                'source_id'   => $transfer->id,
                'movement_type' => InventoryMovementType::TRANSFER_IN,
                'quantity'      => $quantity,
            ]);

            // Record restock fulfillment movement
            InventoryMovement::create([
                'stock_id'      => $toStock->id,
                'batch_id'      => $toBatch->id,
                'inventory_id'  => $toInventory->id,
                'product_id'    => $restockItem->product_id,
                'source_id'   => $restock->id,
                'movement_type' => InventoryMovementType::RESTOCK_RECEIVED,
                'quantity'      => $quantity,
            ]);

            $restockItem->update([
                'fulfilled_quantity' => $quantity,
            ]);
        }

        return $transfer;
    }

    private function fulfillNoAction(Restock $restock): ?Purchase
    {
        foreach ($restock->items as $restockItem) {
            $restockItem->update([
                'fulfilled_quantity' => 0,
            ]);
        }

        return null;
    }

}
