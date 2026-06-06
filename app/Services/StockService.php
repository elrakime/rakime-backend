<?php

namespace App\Services;

use App\Models\Stock;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class StockService
{
    public function list(Request $request): LengthAwarePaginator
    {
        return QueryBuilder::for(Stock::class, $request)
            ->with(['inventory', 'product', 'source'])
            ->allowedFilters(
                AllowedFilter::exact('inventory_id'),
                AllowedFilter::exact('product_id'),
                AllowedFilter::exact('source_id'),
                AllowedFilter::exact('source_type'),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->whereHas('product', function ($q) use ($value) {
                        $q->where('name', 'like', "%{$value}%")
                          ->orWhere('barcode', 'like', "%{$value}%");
                    });
                }),
            )
            ->allowedSorts(
                AllowedSort::field('current_quantity'),
                AllowedSort::field('purchased_at'),
                AllowedSort::field('created_at'),
            )
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 15))
            ->appends($request->query());
    }

    public function create(array $data, Request $request): Stock
    {
        $stock = Stock::create([
            'inventory_id'      => $data['inventory_id'],
            'product_id'        => $data['product_id'],
            'source_id'         => $data['source_id'] ?? null,
            'source_type'       => $data['source_type'] ?? null,
            'initial_quantity'  => $data['initial_quantity'],
            'current_quantity'  => $data['current_quantity'] ?? $data['initial_quantity'],
            'purchase_price'    => $data['purchase_price'] ?? 0,
            'selling_price'     => $data['selling_price'] ?? 0,
            'installment_price' => $data['installment_price'] ?? 0,
            'purchased_at'      => $data['purchased_at'] ?? now(),
        ]);

        return $stock->load(['inventory', 'product', 'source']);
    }

    public function show(Stock $stock): Stock
    {
        return $stock->loadMissing(['inventory', 'product', 'source']);
    }

    public function update(Stock $stock, array $data, Request $request): Stock
    {
        $stock->update(array_filter([
            'inventory_id'      => $data['inventory_id'] ?? null,
            'product_id'        => $data['product_id'] ?? null,
            'source_id'         => array_key_exists('source_id', $data) ? $data['source_id'] : null,
            'source_type'       => array_key_exists('source_type', $data) ? $data['source_type'] : null,
            'initial_quantity'  => $data['initial_quantity'] ?? null,
            'current_quantity'  => $data['current_quantity'] ?? null,
            'purchase_price'    => $data['purchase_price'] ?? null,
            'selling_price'     => $data['selling_price'] ?? null,
            'installment_price' => $data['installment_price'] ?? null,
            'purchased_at'      => $data['purchased_at'] ?? null,
        ], fn ($v) => $v !== null));

        return $stock->refresh()->loadMissing(['inventory', 'product', 'source']);
    }

    public function delete(Stock $stock): void
    {
        $stock->delete();
    }
}
