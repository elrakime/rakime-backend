<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Models\Batch;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Price;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Traits\ScopesByUserBranches;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class SaleService
{
    use ScopesByUserBranches;
    public function list(Request $request): LengthAwarePaginator
    {
        $query = Sale::query();

        $this->scopeByUserBranches($query);

        return QueryBuilder::for($query, $request)
            ->with(['user', 'branch', 'client', 'items.product', 'items.stock'])
            ->allowedFilters(
                AllowedFilter::exact('branch_id'),
                AllowedFilter::exact('client_id'),
                AllowedFilter::partial('reference'),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('reference', 'like', "%{$value}%")
                          ->orWhere('note', 'like', "%{$value}%");
                    });
                }),
            )
            ->allowedSorts(
                AllowedSort::field('reference'),
                AllowedSort::field('total_amount'),
                AllowedSort::field('sold_at'),
                AllowedSort::field('created_at'),
            )
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 15))
            ->appends($request->query());
    }

    public function create(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            $inventory = $this->getBranchInventory($data['branch_id']);
            $resolvedItems = $this->resolveItems($inventory->id, $data['items']);

            $totalAmount = collect($resolvedItems)->sum(fn ($item) => $item['quantity'] * $item['price']);

            $sale = Sale::create([
                'user_id'      => $data['user_id'] ?? auth()->id(),
                'branch_id'    => $data['branch_id'],
                'client_id'    => $data['client_id'] ?? null,
                'total_amount' => $totalAmount,
                'note'         => $data['note'] ?? null,
                'sold_at'      => $data['sold_at'] ?? now(),
            ]);

            foreach ($resolvedItems as $item) {
                SaleItem::create([
                    'sale_id'    => $sale->id,
                    'product_id' => $item['product_id'],
                    'stock_id'   => $item['stock_id'],
                    'quantity'   => $item['quantity'],
                    'price'      => $item['price'],
                ]);
            }

            $this->deductStock($sale);

            return $sale->load(['user', 'branch', 'client', 'items.product', 'items.stock']);
        });
    }

    public function show(Sale $sale): Sale
    {
        return $sale->loadMissing(['user', 'branch', 'client', 'items.product', 'items.stock']);
    }

    public function update(Sale $sale, array $data): Sale
    {
        $sale->update([
            'branch_id' => $data['branch_id'] ?? $sale->branch_id,
            'client_id' => $data['client_id'] ?? $sale->client_id,
            'note'      => $data['note'] ?? $sale->note,
            'sold_at'   => $data['sold_at'] ?? $sale->sold_at,
        ]);

        return $sale->fresh()->loadMissing(['user', 'branch', 'client', 'items.product', 'items.stock']);
    }

    public function delete(Sale $sale): void
    {
        DB::transaction(function () use ($sale) {
            $sale->items()->delete();
            $sale->inventoryMovements()->delete();
            $sale->delete();
        });
    }

    private function deductStock(Sale $sale): void
    {
        $sale->loadMissing('items');

        foreach ($sale->items as $item) {
            $remaining = $item->quantity;

            $batches = Batch::with('stock')
                ->where('stock_id', $item->stock_id)
                ->where('current_quantity', '>', 0)
                ->orderBy('purchased_at')
                ->get();

            foreach ($batches as $batch) {
                $deduct = min($remaining, $batch->current_quantity);
                $batch->decrement('current_quantity', $deduct);

                InventoryMovement::create([
                    'stock_id'      => $item->stock_id,
                    'batch_id'      => $batch->id,
                    'inventory_id'  => $batch->stock->inventory_id,
                    'product_id'    => $item->product_id,
                    'moveable_id'   => $sale->id,
                    'movement_type' => InventoryMovementType::SALE,
                    'quantity'      => $deduct,
                ]);

                $remaining -= $deduct;
                if ($remaining <= 0) {
                    break;
                }
            }
        }
    }

    private function getBranchInventory(int $branchId): Inventory
    {
        $inventory = Inventory::where('branch_id', $branchId)->first();

        if (!$inventory) {
            throw new \Exception(__('sales.no_inventory_for_branch'), 422);
        }

        return $inventory;
    }

    /**
     * Validates that each stock belongs to the branch inventory and has sufficient quantity,
     * then resolves product_id and price.
     *
     * If price_id is provided, uses that specific price; otherwise falls back to the
     * stock's latest selling price.
     *
     * @return array[] items enriched with product_id and price
     */
    private function resolveItems(int $inventoryId, array $items): array
    {
        $stockIds = array_column($items, 'stock_id');
        $priceIds = array_filter(array_column($items, 'price_id'));

        $stocks = Stock::where('inventory_id', $inventoryId)
            ->whereIn('id', $stockIds)
            ->withSum('batches as total_current', 'current_quantity')
            ->with('sellingPrice')
            ->get()
            ->keyBy('id');

        $prices = [];
        if (!empty($priceIds)) {
            $prices = Price::whereIn('id', $priceIds)->get()->keyBy('id');
        }

        $resolved = [];

        foreach ($items as $item) {
            $stockId = $item['stock_id'];

            if (!isset($stocks[$stockId])) {
                throw new \Exception(
                    __('sales.stock_not_in_branch_inventory', ['stock' => $stockId]), 422
                );
            }

            $stock = $stocks[$stockId];

            if (($stock->total_current ?? 0) < $item['quantity']) {
                throw new \Exception(
                    __('sales.insufficient_stock', ['stock' => $stockId, 'available' => $stock->total_current ?? 0]), 422
                );
            }

            $price = isset($item['price_id'])
                ? $prices[$item['price_id']]->amount ?? 0
                : $stock->sellingPrice?->amount ?? 0;

            $resolved[] = [
                'stock_id'   => $stockId,
                'product_id' => $stock->product_id,
                'quantity'   => $item['quantity'],
                'price'      => $price,
            ];
        }

        return $resolved;
    }

}
