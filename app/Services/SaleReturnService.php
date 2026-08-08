<?php

namespace App\Services;

use App\Enums\SaleReturnStatus;
use App\Models\Batch;
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
        if ($saleReturn->status === SaleReturnStatus::COMPLETED) {
            throw new Exception(__('sale_returns.already_approved'), 422);
        }

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
                $totalReturnAmount += $returnItem->quantity * $saleItem->price;

                $batch = Batch::where('stock_id', $saleItem->stock_id)
                    ->orderBy('created_at')
                    ->first();

                if ($batch) {
                    $stock = $batch->stock;
                    $oldQuantity = $stock->batches()->sum('current_quantity');
                    $batch->increment('current_quantity', $returnItem->quantity);

                    $this->inventoryService->saleReturn(
                        stockId: $batch->stock_id,
                        inventoryId: $stock->inventory_id,
                        productId: $saleItem->product_id,
                        oldQuantity: $oldQuantity,
                        quantity: $returnItem->quantity,
                        source: $saleReturn,
                    );
                }
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
