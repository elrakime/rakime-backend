<?php

namespace App\Services;

use App\Models\InventoryMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class InventoryMovementService
{
    public function list(Request $request): LengthAwarePaginator
    {
        return QueryBuilder::for(InventoryMovement::class, $request)
            ->with(['stock.product', 'batch', 'inventory', 'product'])
            ->allowedFilters(
                AllowedFilter::exact('inventory_id'),
                AllowedFilter::exact('stock_id'),
                AllowedFilter::exact('product_id'),
                AllowedFilter::exact('batch_id'),
                AllowedFilter::exact('movement_type'),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where(function ($q) use ($value) {
                        $q->whereHas('product', fn ($p) => $p->where('name', 'like', "%{$value}%"))
                          ->orWhereHas('inventory', fn ($i) => $i->where('name', 'like', "%{$value}%"));
                    });
                }),
            )
            ->allowedSorts(
                AllowedSort::field('movement_type'),
                AllowedSort::field('quantity'),
                AllowedSort::field('created_at'),
            )
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 15))
            ->appends($request->query());
    }
}
