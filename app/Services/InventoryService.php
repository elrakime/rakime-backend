<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Traits\ScopesByUserBranches;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class InventoryService
{
    use ScopesByUserBranches;
    public function list(Request $request): Collection
    {
        $query = Inventory::query();

        $this->scopeByUserBranches($query);

        return QueryBuilder::for($query, $request)
            ->with('branch')
            ->allowedFilters(
                AllowedFilter::partial('name'),
                AllowedFilter::exact('branch_id'),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where('name', 'like', "%{$value}%");
                }),
            )
            ->allowedSorts(
                AllowedSort::field('name'),
                AllowedSort::field('created_at'),
            )
            ->defaultSort('-created_at')
            ->get();
    }

    public function create(array $data): Inventory
    {
        $inventory = Inventory::create([
            'branch_id' => $data['branch_id'] ?? null,
            'name'      => $data['name'],
        ]);

        return $inventory->loadMissing('branch');
    }

    public function show(Inventory $inventory): Inventory
    {
        return $inventory->loadMissing('branch');
    }

    public function update(Inventory $inventory, array $data): Inventory
    {
        $inventory->update(array_filter([
            'branch_id' => array_key_exists('branch_id', $data) ? $data['branch_id'] : $inventory->branch_id,
            'name'      => $data['name'] ?? null,
        ], fn ($v) => $v !== null));

        return $inventory->refresh()->loadMissing('branch');
    }

    public function delete(Inventory $inventory): void
    {
        $inventory->delete();
    }

    // ============================================================
    // MOVEMENT METHODS
    // ============================================================

    /**
     * Record a stock receive (inflow), e.g. from a purchase.
     */
    public function receive(
        int $stockId,
        int $inventoryId,
        int $productId,
        int $oldQuantity,
        int $quantity,
        ?Model $source = null,
        array $allocations = [],
    ): InventoryMovement {
        return $this->recordMovement(
            $stockId,
            $inventoryId,
            $productId,
            InventoryMovementType::RECEIVE,
            $oldQuantity,
            $quantity,
            $source,
            $allocations,
        );
    }

    /**
     * Record a stock return (outflow), e.g. a purchase return.
     */
    public function returnOut(
        int $stockId,
        int $inventoryId,
        int $productId,
        int $oldQuantity,
        int $quantity,
        ?Model $source = null,
        array $allocations = [],
    ): InventoryMovement {
        return $this->recordMovement(
            $stockId,
            $inventoryId,
            $productId,
            InventoryMovementType::RETURN,
            $oldQuantity,
            -abs($quantity),
            $source,
            $allocations,
        );
    }

    /**
     * Record a transfer out (outflow).
     */
    public function transferOut(
        int $stockId,
        int $inventoryId,
        int $productId,
        int $oldQuantity,
        int $quantity,
        ?Model $source = null,
        array $allocations = [],
    ): InventoryMovement {
        return $this->recordMovement(
            $stockId,
            $inventoryId,
            $productId,
            InventoryMovementType::TRANSFER_OUT,
            $oldQuantity,
            -abs($quantity),
            $source,
            $allocations,
        );
    }

    /**
     * Record a transfer in (inflow).
     */
    public function transferIn(
        int $stockId,
        int $inventoryId,
        int $productId,
        int $oldQuantity,
        int $quantity,
        ?Model $source = null,
        array $allocations = [],
    ): InventoryMovement {
        return $this->recordMovement(
            $stockId,
            $inventoryId,
            $productId,
            InventoryMovementType::TRANSFER_IN,
            $oldQuantity,
            $quantity,
            $source,
            $allocations,
        );
    }

    /**
     * Record a sale deduction (outflow).
     */
    public function sale(
        int $stockId,
        int $inventoryId,
        int $productId,
        int $oldQuantity,
        int $quantity,
        ?Model $source = null,
        array $allocations = [],
    ): InventoryMovement {
        return $this->recordMovement(
            $stockId,
            $inventoryId,
            $productId,
            InventoryMovementType::SALE,
            $oldQuantity,
            -abs($quantity),
            $source,
            $allocations,
        );
    }

    /**
     * Record an expired stock deduction (outflow).
     */
    public function expired(
        int $stockId,
        int $inventoryId,
        int $productId,
        int $oldQuantity,
        int $quantity,
        ?Model $source = null,
        array $allocations = [],
    ): InventoryMovement {
        return $this->recordMovement(
            $stockId,
            $inventoryId,
            $productId,
            InventoryMovementType::EXPIRED,
            $oldQuantity,
            -abs($quantity),
            $source,
            $allocations,
        );
    }

    /**
     * Record a restock receive (inflow).
     */
    public function restockReceived(
        int $stockId,
        int $inventoryId,
        int $productId,
        int $oldQuantity,
        int $quantity,
        ?Model $source = null,
        array $allocations = [],
    ): InventoryMovement {
        return $this->recordMovement(
            $stockId,
            $inventoryId,
            $productId,
            InventoryMovementType::RESTOCK_RECEIVED,
            $oldQuantity,
            $quantity,
            $source,
            $allocations,
        );
    }

    /**
     * Record a transfer cancel reversal (inflow back to source).
     */
    public function transferCancel(
        int $stockId,
        int $inventoryId,
        int $productId,
        int $oldQuantity,
        int $quantity,
        ?Model $source = null,
        array $allocations = [],
    ): InventoryMovement {
        return $this->recordMovement(
            $stockId,
            $inventoryId,
            $productId,
            InventoryMovementType::TRANSFER_CANCEL,
            $oldQuantity,
            $quantity,
            $source,
            $allocations,
        );
    }

    /**
     * Record a sale return credit (inflow).
     */
    public function saleReturn(
        int $stockId,
        int $inventoryId,
        int $productId,
        int $oldQuantity,
        int $quantity,
        ?Model $source = null,
        array $allocations = [],
    ): InventoryMovement {
        return $this->recordMovement(
            $stockId,
            $inventoryId,
            $productId,
            InventoryMovementType::SALE_RETURN,
            $oldQuantity,
            $quantity,
            $source,
            $allocations,
        );
    }

    /**
     * Record a sale update adjustment (positive or negative).
     */
    public function saleUpdate(
        int $stockId,
        int $inventoryId,
        int $productId,
        int $oldQuantity,
        int $quantity,
        ?Model $source = null,
        array $allocations = [],
    ): InventoryMovement {
        return $this->recordMovement(
            $stockId,
            $inventoryId,
            $productId,
            InventoryMovementType::SALE_UPDATE,
            $oldQuantity,
            $quantity,
            $source,
            $allocations,
        );
    }

    // ============================================================
    // INTERNAL
    // ============================================================

    /**
     * Core: create an inventory movement record.
     */
    private function recordMovement(
        int $stockId,
        int $inventoryId,
        int $productId,
        InventoryMovementType $type,
        int $oldQuantity,
        int $quantity,
        ?Model $source = null,
        array $allocations = [],
    ): InventoryMovement {
        $movement = InventoryMovement::create([
            'stock_id'      => $stockId,
            'inventory_id'  => $inventoryId,
            'product_id'    => $productId,
            'source_type'   => $source ? get_class($source) : null,
            'source_id'     => $source?->id,
            'movement_type' => $type,
            'old_quantity'  => $oldQuantity,
            'new_quantity'  => $oldQuantity + $quantity,
            'quantity'      => $quantity,
        ]);

        if ($allocations !== []) {
            $movement->allocations()->createMany(
                collect($allocations)->map(fn (array $allocation) => [
                    'batch_id'       => $allocation['batch_id'],
                    'quantity'       => $allocation['quantity'],
                    'purchase_price' => $allocation['purchase_price'],
                ])->all()
            );
        }

        return $movement;
    }
}
