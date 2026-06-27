<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Models\Batch;
use App\Models\Expiration;
use App\Models\ExpirationItem;
use App\Models\InventoryMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class ExpirationService
{
    public function list(Request $request): LengthAwarePaginator
    {
        return QueryBuilder::for(Expiration::class, $request)
            ->with(['user', 'inventory', 'items.stock.product'])
            ->allowedFilters(
                AllowedFilter::exact('inventory_id'),
                AllowedFilter::exact('user_id'),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('reference', 'like', "%{$value}%")
                          ->orWhere('note', 'like', "%{$value}%");
                    });
                }),
            )
            ->allowedSorts(
                AllowedSort::field('reference'),
                AllowedSort::field('reported_at'),
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
                'user_id'      => $data['user_id'],
                'inventory_id' => $data['inventory_id'],
                'note'         => $data['note'] ?? null,
                'reported_at'  => $data['reported_at'] ?? now(),
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

            return $expiration->load(['user', 'inventory', 'items.stock.product']);
        });
    }

    public function show(Expiration $expiration): Expiration
    {
        return $expiration->loadMissing(['user', 'inventory', 'items.stock.product']);
    }

    public function update(Expiration $expiration, array $data): Expiration
    {
        if ($expiration->approved_at) {
            throw new \Exception(__('expirations.cannot_update_approved'), 422);
        }

        return DB::transaction(function () use ($expiration, $data) {
            $expiration->update(array_filter([
                'inventory_id' => $data['inventory_id'] ?? null,
                'note'         => $data['note'] ?? null,
                'reported_at'  => $data['reported_at'] ?? null,
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
        if ($expiration->approved_at) {
            return $expiration;
        }

        $expiration->loadMissing(['items.stock.product']);

        return DB::transaction(function () use ($expiration) {
            $expiration->update(['approved_at' => now()]);

            foreach ($expiration->items as $item) {
                $remaining = $item->quantity;

                $batches = Batch::where('stock_id', $item->stock_id)
                    ->where('current_quantity', '>', 0)
                    ->orderBy('purchased_at')
                    ->get();

                foreach ($batches as $batch) {
                    $deduct = min($remaining, $batch->current_quantity);
                    $batch->decrement('current_quantity', $deduct);

                    InventoryMovement::create([
                        'stock_id'      => $item->stock_id,
                        'batch_id'      => $batch->id,
                        'inventory_id'  => $expiration->inventory_id,
                        'product_id'    => $item->stock->product_id,
                        'source_id'   => $expiration->id,
                        'movement_type' => InventoryMovementType::EXPIRED,
                        'quantity'      => $deduct,
                    ]);

                    $remaining -= $deduct;
                    if ($remaining <= 0) {
                        break;
                    }
                }
            }

            return $expiration->fresh()->loadMissing(['user', 'inventory', 'items.stock.product']);
        });
    }

    public function delete(Expiration $expiration): void
    {
        $expiration->items()->delete();
        $expiration->delete();
    }

}
