<?php

namespace App\Services;

use App\Models\Expiration;
use App\Models\ExpirationItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class ExpirationService
{
    public function list(Request $request): LengthAwarePaginator
    {
        return QueryBuilder::for(Expiration::class, $request)
            ->with(['user', 'inventory', 'items.product', 'items.stock', 'items.batch'])
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
        $expiration = Expiration::create([
            'user_id'      => $data['user_id'],
            'inventory_id' => $data['inventory_id'],
            'reference'    => $data['reference'],
            'note'         => $data['note'] ?? null,
            'reported_at'  => $data['reported_at'] ?? now(),
        ]);

        if (!empty($data['items'])) {
            foreach ($data['items'] as $item) {
                ExpirationItem::create([
                    'expiration_id' => $expiration->id,
                    'product_id'    => $item['product_id'],
                    'stock_id'      => $item['stock_id'],
                    'batch_id'      => $item['batch_id'] ?? null,
                    'quantity'      => $item['quantity'],
                    'reason'        => $item['reason'] ?? null,
                ]);
            }
        }

        return $expiration->load(['user', 'inventory', 'items.product', 'items.stock', 'items.batch']);
    }

    public function show(Expiration $expiration): Expiration
    {
        return $expiration->loadMissing(['user', 'inventory', 'items.product', 'items.stock', 'items.batch']);
    }

    public function update(Expiration $expiration, array $data): Expiration
    {
        $expiration->update(array_filter([
            'inventory_id' => $data['inventory_id'] ?? null,
            'reference'    => $data['reference'] ?? null,
            'note'         => $data['note'] ?? null,
            'reported_at'  => $data['reported_at'] ?? null,
        ], fn ($v) => $v !== null));

        return $expiration->refresh()->loadMissing(['user', 'inventory', 'items.product', 'items.stock', 'items.batch']);
    }

    public function delete(Expiration $expiration): void
    {
        $expiration->items()->delete();
        $expiration->delete();
    }
}
