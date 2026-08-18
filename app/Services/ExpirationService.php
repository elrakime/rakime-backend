<?php

namespace App\Services;

use App\Enums\ExpirationStatus;
use App\Models\Batch;
use App\Models\Expiration;
use App\Models\ExpirationItem;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class ExpirationService
{
    public function __construct(private readonly InventoryService $inventoryService) {}
    public function list(Request $request): LengthAwarePaginator
    {
        return QueryBuilder::for(Expiration::class, $request)
            ->with(['user', 'inventory', 'items.stock.product'])
            ->allowedFilters(
                AllowedFilter::exact('inventory_id'),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('reference', 'like', "%{$value}%")
                          ->orWhere('note', 'like', "%{$value}%");
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

    public function create(array $data): Expiration
    {
        return DB::transaction(function () use ($data) {
            $expiration = Expiration::create([
                'inventory_id' => $data['inventory_id'],
                'note'         => $data['note'] ?? null,
            ]);

            if (!empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    ExpirationItem::create([
                        'expiration_id' => $expiration->id,
                        'stock_id'      => $item['stock_id'],
                        'quantity'      => $item['quantity'],
                        'reason'        => $item['reason'] ?? null,
                    ]);
                }
            }

            return $expiration->fresh()->load(['user', 'inventory', 'items.stock.product']);
        });
    }

    public function show(Expiration $expiration): Expiration
    {
        return $expiration->loadMissing(['user', 'inventory', 'items.stock.product']);
    }

    public function update(Expiration $expiration, array $data): Expiration
    {
        return DB::transaction(function () use ($expiration, $data) {
            $expiration->update(array_filter([
                'inventory_id' => $data['inventory_id'] ?? null,
                'note'         => $data['note'] ?? null,
            ], fn ($v) => $v !== null));

            if (array_key_exists('items', $data)) {
                $expiration->items()->delete();

                foreach ($data['items'] as $item) {
                    ExpirationItem::create([
                        'expiration_id' => $expiration->id,
                        'stock_id'      => $item['stock_id'],
                        'quantity'      => $item['quantity'],
                        'reason'        => $item['reason'] ?? null,
                    ]);
                }
            }

            return $expiration->fresh()->loadMissing(['user', 'inventory', 'items.stock.product']);
        });
    }

    public function approve(Expiration $expiration): Expiration
    {
        $expiration->loadMissing(['items.stock.product']);

        return DB::transaction(function () use ($expiration) {
            foreach ($expiration->items as $item) {
                $remaining = $item->quantity;

                $batches = Batch::where('stock_id', $item->stock_id)
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

                $this->inventoryService->expired(
                    stockId: $item->stock_id,
                    inventoryId: $expiration->inventory_id,
                    productId: $item->stock->product_id,
                    oldQuantity: $oldQuantity,
                    quantity: $item->quantity,
                    source: $expiration,
                    allocations: $allocations,
                );
            }

            $expiration->update(['status' => ExpirationStatus::APPROVED]);

            return $expiration->fresh()->loadMissing(['user', 'inventory', 'items.stock.product']);
        });
    }

    public function delete(Expiration $expiration): void
    {
        $expiration->items()->delete();
        $expiration->delete();
    }

}
