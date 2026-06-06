<?php

namespace App\Services;

use App\Models\Price;
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
            ->with(['inventory', 'product', 'batches', 'prices'])
            ->allowedFilters(
                AllowedFilter::exact('inventory_id'),
                AllowedFilter::exact('product_id'),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->whereHas('product', function ($q) use ($value) {
                        $q->where('name', 'like', "%{$value}%")
                          ->orWhere('barcode', 'like', "%{$value}%");
                    });
                }),
            )
            ->allowedSorts(
                AllowedSort::field('created_at'),
            )
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 15))
            ->appends($request->query());
    }

    public function create(array $data, Request $request): Stock
    {
        $stock = Stock::create([
            'inventory_id' => $data['inventory_id'],
            'product_id'   => $data['product_id'],
        ]);

        if (isset($data['purchase_price']) || isset($data['selling_price']) || isset($data['installment_price'])) {
            $prices = [];

            if (isset($data['selling_price'])) {
                $prices[] = new Price(['type' => 'selling', 'amount' => $data['selling_price']]);
            }
            if (isset($data['installment_price'])) {
                $prices[] = new Price(['type' => 'installment', 'amount' => $data['installment_price']]);
            }
            if (isset($data['wholesale_price'])) {
                $prices[] = new Price(['type' => 'wholesale', 'amount' => $data['wholesale_price']]);
            }

            if (!empty($prices)) {
                $stock->prices()->saveMany($prices);
            }
        }

        if (isset($data['initial_quantity'])) {
            $batch = $stock->batches()->create([
                'source_id'         => $data['source_id'] ?? null,
                'source_type'       => $data['source_type'] ?? null,
                'purchase_price'    => $data['purchase_price'] ?? 0,
                'initial_quantity'  => $data['initial_quantity'],
                'current_quantity'  => $data['current_quantity'] ?? $data['initial_quantity'],
                'purchased_at'      => $data['purchased_at'] ?? now(),
            ]);
        }

        return $stock->load(['inventory', 'product', 'batches', 'prices']);
    }

    public function show(Stock $stock): Stock
    {
        return $stock->loadMissing(['inventory', 'product', 'batches', 'prices']);
    }

    public function update(Stock $stock, array $data, Request $request): Stock
    {
        $stock->update(array_filter([
            'inventory_id' => $data['inventory_id'] ?? null,
            'product_id'   => $data['product_id'] ?? null,
        ], fn ($v) => $v !== null));

        return $stock->refresh()->loadMissing(['inventory', 'product', 'batches', 'prices']);
    }

    public function delete(Stock $stock): void
    {
        $stock->delete();
    }
}
