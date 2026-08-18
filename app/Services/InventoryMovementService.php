<?php

namespace App\Services;

use App\Models\Expiration;
use App\Models\InventoryMovement;
use App\Models\InventoryTransfer;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Restock;
use App\Models\Sale;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class InventoryMovementService
{
    public function list(Request $request): LengthAwarePaginator
    {
        $query = InventoryMovement::query();

        $query->byUserBranches();

        return QueryBuilder::for($query, $request)
            ->with(['stock.product', 'inventory', 'product', 'source' => function ($morphTo) {
                $morphTo->morphWith([
                    Expiration::class        => [],
                    InventoryTransfer::class => ['fromInventory', 'toInventory'],
                    Purchase::class          => ['supplier'],
                    PurchaseReturn::class    => ['purchase.supplier'],
                    Restock::class           => [],
                    Sale::class              => ['client'],
                ]);
            }])
            ->allowedFilters(
                AllowedFilter::exact('inventory_id'),
                AllowedFilter::exact('stock_id'),
                AllowedFilter::exact('product_id'),
                AllowedFilter::exact('movement_type'),
                AllowedFilter::callback('from_date', function ($query, string $value) {
                    $query->whereDate('created_at', '>=', $value);
                }),
                AllowedFilter::callback('to_date', function ($query, string $value) {
                    $query->whereDate('created_at', '<=', $value);
                }),
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
