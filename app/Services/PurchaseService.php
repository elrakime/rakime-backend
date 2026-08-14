<?php

namespace App\Services;

use App\Enums\PriceType;
use App\Enums\PurchaseReturnStatus;
use App\Enums\PurchaseStatus;
use App\Models\Inventory;
use App\Models\Price;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\Restock;
use App\Models\Stock;
use App\Traits\ScopesByUserBranches;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class PurchaseService
{
    use ScopesByUserBranches;

    public function __construct(private readonly InventoryService $inventoryService)
    {
    }
    public function list(Request $request): LengthAwarePaginator
    {
        $query = Purchase::query();

        $this->scopeByUserBranches($query);

        $completedReturnAmount = PurchaseReturn::query()
            ->selectRaw('coalesce(sum(purchase_return_items.quantity * purchase_items.price), 0)')
            ->join('purchase_return_items', 'purchase_returns.id', '=', 'purchase_return_items.purchase_return_id')
            ->join('purchase_items', 'purchase_return_items.purchase_item_id', '=', 'purchase_items.id')
            ->whereColumn('purchase_returns.purchase_id', 'purchases.id')
            ->where('purchase_returns.status', PurchaseReturnStatus::COMPLETED->value);

        return QueryBuilder::for($query, $request)
            ->with(['supplier', 'branch', 'items.product', 'items.returnItems.purchaseReturn', 'inventory', 'returns.items.purchaseItem'])
            ->allowedFilters(
                AllowedFilter::partial('reference'),
                AllowedFilter::exact('supplier_id'),
                AllowedFilter::exact('branch_id'),
                AllowedFilter::exact('inventory_id'),
                AllowedFilter::exact('status'),
                AllowedFilter::callback('payment_status', function ($query, string $value) use ($completedReturnAmount) {
                    $query->where(function ($q) use ($value, $completedReturnAmount) {
                        $netAmountSql = 'total_amount - (' . $completedReturnAmount->toSql() . ')';

                        match ($value) {
                            'unpaid' => $q->where('paid_amount', '<=', 0),
                            'partially_paid' => $q->where('paid_amount', '>', 0)
                                ->whereRaw('paid_amount < ' . $netAmountSql, $completedReturnAmount->getBindings()),
                            'paid' => $q->whereRaw('paid_amount >= ' . $netAmountSql, $completedReturnAmount->getBindings()),
                            default => null,
                        };
                    });
                }),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('reference', 'like', "%{$value}%");
                    });
                }),
            )
            ->allowedSorts(
                AllowedSort::field('total_amount'),
                AllowedSort::field('created_at'),
            )
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 15))
            ->appends($request->query());
    }

    public function create(array $data): Purchase
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'];

            $totalAmount = collect($items)->sum(fn($item) => $item['quantity'] * $item['price']);

            $purchase = Purchase::create([
                'supplier_id' => $data['supplier_id'],
                'branch_id' => $data['branch_id'],
                'status' => PurchaseStatus::PENDING,
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'note' => $data['note'] ?? null,
            ]);

            $purchase->items()->createMany(
                collect($items)->map(fn($item) => [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ])->all()
            );

            return $purchase->loadMissing(['supplier', 'items.product', 'returns.items.purchaseItem']);
        });
    }

    public function show(Purchase $purchase): Purchase
    {
        return $purchase->loadMissing(['supplier', 'items.product', 'payments', 'inventory', 'returns.items.purchaseItem']);
    }

    public function update(Purchase $purchase, array $data): Purchase
    {
        if ($purchase->status !== PurchaseStatus::PENDING) {
            throw new Exception(__('purchases.not_pending'), 422);
        }

        return DB::transaction(function () use ($purchase, $data) {
            $purchase->update(array_filter([
                'supplier_id' => $data['supplier_id'] ?? null,
                'branch_id' => $data['branch_id'] ?? null,
                'note' => $data['note'] ?? null,
            ], fn($v) => $v !== null));

            if (array_key_exists('items', $data)) {
                $items = $data['items'];

                $totalAmount = collect($items)->sum(fn($item) => $item['quantity'] * $item['price']);

                $purchase->items()->delete();
                $purchase->items()->createMany(
                    collect($items)->map(fn($item) => [
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                    ])->all()
                );

                $purchase->update(['total_amount' => $totalAmount]);
            }

            return $purchase->refresh()->loadMissing(['supplier', 'items.product', 'payments', 'returns.items.purchaseItem']);
        });
    }

    public function delete(Purchase $purchase): void
    {
        if ($purchase->status !== PurchaseStatus::PENDING) {
            throw new Exception(__('purchases.not_pending'), 422);
        }

        $purchase->delete();
    }

    public function cancel(Purchase $purchase): Purchase
    {
        if ($purchase->status !== PurchaseStatus::PENDING) {
            throw new Exception(__('purchases.not_pending'), 422);
        }

        $purchase->update(['status' => PurchaseStatus::CANCELED]);

        return $purchase->refresh()->loadMissing(['supplier', 'items.product', 'payments', 'returns.items.purchaseItem']);
    }

    public function receive(Purchase $purchase, array $data): Purchase
    {
        if ($purchase->status !== PurchaseStatus::PENDING) {
            throw new Exception(__('purchases.not_pending'), 422);
        }

        return DB::transaction(function () use ($purchase, $data) {
            $inventoryId = $this->resolveInventoryId($purchase, $data['inventory_id'] ?? null);

            $purchase->update([
                'status' => PurchaseStatus::COMPLETED,
                'inventory_id' => $inventoryId,
            ]);

            // Index optional per-item pricing overrides by product_id
            $pricingByProduct = collect($data['items'] ?? [])->keyBy('product_id');

            // Validate prices before processing
            $this->validatePrices($purchase, $inventoryId, $pricingByProduct);

            // If this purchase was created from a restock, load the restock to update fulfilled quantities
            $restock = Restock::where('fulfilled_with_id', $purchase->id)
                ->where('fulfilled_with_type', Purchase::class)
                ->first();

            foreach ($purchase->items as $item) {
                $pricing = $pricingByProduct->get($item->product_id, []);

                $stock = Stock::firstOrCreate([
                    'inventory_id' => $inventoryId,
                    'product_id' => $item->product_id,
                ]);

                $oldQuantity = $stock->batches()->sum('current_quantity');

                $batch = $stock->batches()->create([
                    'source_id' => $item->id,
                    'source_type' => PurchaseItem::class,
                    'purchase_price' => $item->price,
                    'initial_quantity' => $item->quantity,
                    'current_quantity' => $item->quantity,
                ]);

                $prices = collect();
                foreach ($pricing['selling_prices'] ?? [] as $amount) {
                    $prices->push(new Price(['type' => PriceType::SELLING, 'amount' => $amount]));
                }
                foreach ($pricing['installment_prices'] ?? [] as $amount) {
                    $prices->push(new Price(['type' => PriceType::INSTALLMENT, 'amount' => $amount]));
                }

                if ($prices->isNotEmpty()) {
                    $stock->prices()->saveMany($prices);
                }

                $this->inventoryService->receive(
                    stockId: $stock->id,
                    inventoryId: $inventoryId,
                    productId: $item->product_id,
                    oldQuantity: $oldQuantity,
                    quantity: $item->quantity,
                    source: $purchase,
                    allocations: [
                        [
                            'batch_id'       => $batch->id,
                            'quantity'       => $item->quantity,
                            'purchase_price' => $item->price,
                        ],
                    ],
                );

                // Update restock fulfilled_quantity if linked
                if ($restock) {
                    $restockItem = $restock->items()->where('product_id', $item->product_id)->first();
                    if ($restockItem) {
                        $restockItem->increment('fulfilled_quantity', $item->quantity);
                    }
                }
            }

            return $purchase->refresh()->loadMissing(['supplier', 'items.product', 'payments', 'returns.items.purchaseItem']);
        });
    }

    private function validatePrices(Purchase $purchase, int $inventoryId, \Illuminate\Support\Collection $pricingByProduct): void
    {
        if ($pricingByProduct->isEmpty()) {
            return;
        }

        // Fetch max old-batch purchase prices in a single query, keyed by product_id
        $maxOldBatchPrices = Stock::where('inventory_id', $inventoryId)
            ->whereIn('product_id', $pricingByProduct->keys())
            ->join('batches', 'stocks.id', '=', 'batches.stock_id')
            ->where('batches.current_quantity', '>', 0)
            ->groupBy('stocks.product_id')
            ->selectRaw('stocks.product_id, MAX(batches.purchase_price) as max_price')
            ->pluck('max_price', 'stocks.product_id')
            ->map(fn($v) => (int) $v);

        foreach ($purchase->items as $item) {
            $pricing = $pricingByProduct->get($item->product_id);
            $maxOldBatchPrice = $maxOldBatchPrices->get($item->product_id, 0);

            if (!$pricing) {
                continue;
            }



            foreach ($pricing['selling_prices'] ?? [] as $amount) {
                $amount = (int) $amount;

                if ($amount <= $item->price) {
                    throw new Exception(__('purchases.selling_price_below_purchase', [
                        'price' => $item->price,
                    ]), 422);
                }
                if ($maxOldBatchPrice > 0 && $amount <= $maxOldBatchPrice) {
                    throw new Exception(__('purchases.selling_price_below_old_batches', [
                        'price' => $maxOldBatchPrice,
                    ]), 422);
                }
            }

            foreach ($pricing['installment_prices'] ?? [] as $amount) {
                $amount = (int) $amount;

                if ($amount <= $item->price) {
                    throw new Exception(__('purchases.installment_price_below_purchase', [
                        'price' => $item->price,
                    ]), 422);
                }
                if ($maxOldBatchPrice > 0 && $amount <= $maxOldBatchPrice) {
                    throw new Exception(__('purchases.installment_price_below_old_batches', [
                        'price' => $maxOldBatchPrice,
                    ]), 422);
                }
            }
        }
    }

    private function resolveInventoryId(Purchase $purchase, ?int $inventoryId): int
    {
        if ($inventoryId) {
            $inventory = Inventory::findOrFail($inventoryId);

            if ($purchase->branch_id && $inventory->branch_id !== $purchase->branch_id) {
                throw new Exception(__('purchases.inventory_branch_mismatch'), 422);
            }

            return $inventoryId;
        }

        $branchInventories = Inventory::where('branch_id', $purchase->branch_id)->get();

        if ($branchInventories->isEmpty()) {
            throw new Exception(__('purchases.no_branch_inventories'), 422);
        }

        if ($branchInventories->count() > 1) {
            throw new Exception(__('purchases.multiple_branch_inventories'), 422);
        }

        return $branchInventories->first()->id;
    }

}
