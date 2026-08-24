<?php

namespace App\Services;

use App\Enums\InventoryTransferStatus;
use App\Enums\PurchaseStatus;
use App\Enums\RestockStatus;
use App\Models\Inventory;
use App\Models\InventoryTransfer;
use App\Models\InventoryTransferItem;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Restock;
use App\Models\RestockItem;
use App\Models\Stock;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class RestockService
{

    public function __construct(private readonly InventoryService $inventoryService) {}
    public function list(Request $request): LengthAwarePaginator
    {
        $query = Restock::query();

        $query->byUserBranches();

        return QueryBuilder::for($query, $request)
            ->with(['user', 'branch', 'items.product'])
            ->with('fulfilledWith', function ($morphTo) {
                $morphTo->morphWith([
                    Purchase::class          => ['inventory', 'supplier', 'returns.items.purchaseItem'],
                    InventoryTransfer::class => ['fromInventory', 'toInventory'],
                ]);
            })
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
                'branch_id' => $data['branch_id'],
                'status'    => RestockStatus::PENDING,
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
        $restock->loadMissing(['user', 'branch', 'items.product', 'fulfilledWith']);
        $restock->loadMorph('fulfilledWith', [
            Purchase::class          => ['inventory', 'supplier', 'returns.items.purchaseItem'],
            InventoryTransfer::class => ['fromInventory', 'toInventory'],
        ]);

        return $restock;
    }

    public function update(Restock $restock, array $data): Restock
    {
        if ($restock->status !== RestockStatus::PENDING) {
            throw new Exception(__('restocks.not_draft'), 422);
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
        if ($restock->status !== RestockStatus::PENDING) {
            throw new Exception(__('restocks.not_draft'), 422);
        }

        DB::transaction(function () use ($restock) {
            $restock->items()->delete();
            $restock->delete();
        });
    }

    public function submit(Restock $restock): Restock
    {
        return $restock->fresh()->loadMissing(['user', 'branch', 'items.product']);
    }

    public function cancel(Restock $restock): Restock
    {
        if ($restock->status !== RestockStatus::PENDING) {
            throw new Exception(__('restocks.cannot_cancel'), 422);
        }

        $restock->update(['status' => RestockStatus::CANCELLED]);

        return $restock->fresh()->loadMissing(['user', 'branch', 'items.product']);
    }

    public function fulfill(Restock $restock, array $data): Restock
    {
        if ($restock->status !== RestockStatus::PENDING) {
            throw new Exception(__('restocks.must_be_submitted'), 422);
        }

        $type = $data['type'];

        return DB::transaction(function () use ($restock, $data, $type) {
            $fulfilledWith = match ($type) {
                'purchase' => $this->fulfillViaPurchase($restock, $data),
                'transfer' => $this->fulfillViaTransfer($restock, $data),
                'none'     => $this->fulfillNoAction($restock),
                default    => throw new Exception(__('restocks.invalid_fulfill_type'), 422),
            };

            $restock->update([
                'status'             => RestockStatus::FULFILLED,
                'fulfilled_with_id'  => $fulfilledWith?->id,
                'fulfilled_with_type' => $fulfilledWith ? get_class($fulfilledWith) : null,
            ]);

            $restock = $restock->fresh()->loadMissing(['user', 'branch', 'items.product', 'fulfilledWith']);
            $restock->loadMorph('fulfilledWith', [
                Purchase::class          => ['inventory', 'supplier', 'returns.items.purchaseItem'],
                InventoryTransfer::class => ['fromInventory', 'toInventory'],
            ]);

            return $restock;
        });
    }

    private function fulfillViaPurchase(Restock $restock, array $data): Purchase
    {
        $restock->loadMissing('branch');

        // Build per-product pricing map from request
        $pricing = collect($data['items'] ?? [])->keyBy('product_id');

        $totalAmount = 0;

        // Create purchase as PENDING — no stock changes yet
        $purchase = Purchase::create([
            'supplier_id'  => $data['supplier_id'],
            'status'       => PurchaseStatus::PENDING,
            'total_amount' => 0,
            'note'         => $data['note'] ?? null,
        ]);

        foreach ($restock->items as $restockItem) {
            $quantity = $restockItem->requested_quantity;
            $price = (float) ($pricing[$restockItem->product_id]['price'] ?? 0);

            $totalAmount += $quantity * $price;

            $purchase->items()->create([
                'product_id' => $restockItem->product_id,
                'quantity'   => $quantity,
                'price'      => $price,
            ]);

            $restockItem->update([
                'fulfilled_quantity' => 0,
            ]);
        }

        $purchase->update(['total_amount' => $totalAmount]);

        $purchase->recalculateAmounts();

        return $purchase;
    }

    private function fulfillViaTransfer(Restock $restock, array $data): InventoryTransfer
    {
        $restock->loadMissing('branch');

        // Find the inventory belonging to the restock's branch (destination)
        $toInventory = Inventory::where('branch_id', $restock->branch_id)->first();

        if (!$toInventory) {
            throw new Exception(__('restocks.no_inventory_for_branch'), 422);
        }

        $fromInventoryId = $data['from_inventory_id'];

        // Validate that source inventory has enough stock for all requested items
        foreach ($restock->items as $restockItem) {
            $quantity = $restockItem->requested_quantity;

            $fromStock = Stock::where('inventory_id', $fromInventoryId)
                ->where('product_id', $restockItem->product_id)
                ->first();

            $available = $fromStock ? $fromStock->batches()->sum('current_quantity') : 0;

            if ($available < $quantity) {
                throw new Exception(
                    __('restocks.insufficient_stock_in_source', [
                        'product' => $restockItem->product_id,
                        'available' => $available,
                        'requested' => $quantity,
                    ]), 422,
                );
            }
        }

        // Create transfer as PENDING — no stock changes yet
        $transfer = InventoryTransfer::create([
            'from_inventory_id' => $fromInventoryId,
            'to_inventory_id'   => $toInventory->id,
            'status'            => InventoryTransferStatus::PENDING,
            'note'              => $data['note'] ?? null,
        ]);

        foreach ($restock->items as $restockItem) {
            $quantity = $restockItem->requested_quantity;

            $fromStock = Stock::where('inventory_id', $fromInventoryId)
                ->where('product_id', $restockItem->product_id)
                ->first();

            // Create transfer item referencing the source stock
            $transfer->items()->create([
                'stock_id' => $fromStock?->id,
                'quantity' => $quantity,
            ]);

            $restockItem->update([
                'fulfilled_quantity' => 0,
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
