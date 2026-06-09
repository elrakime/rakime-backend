<?php

namespace App\Services;

use App\Enums\PriceType;
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
            ->with([
                'inventory', 'product',
                'batches', 'prices',
                'sellingPrice', 'installmentPrice', 'wholesalePrice',
                'currentQuantity', 'initialQuantity',
            ])
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
        $stock = Stock::firstOrCreate([
            'inventory_id' => $data['inventory_id'],
            'product_id'   => $data['product_id'],
        ]);

        if (isset($data['selling_prices']) || isset($data['installment_prices']) || isset($data['wholesale_prices']) || isset($data['purchase_price'])) {
            $prices = collect();

            foreach ($data['selling_prices'] ?? [] as $amount) {
                $prices->push(new Price(['type' => PriceType::SELLING, 'amount' => $amount]));
            }
            foreach ($data['installment_prices'] ?? [] as $amount) {
                $prices->push(new Price(['type' => PriceType::INSTALLMENT, 'amount' => $amount]));
            }
            foreach ($data['wholesale_prices'] ?? [] as $amount) {
                $prices->push(new Price(['type' => PriceType::WHOLESALE, 'amount' => $amount]));
            }

            if ($prices->isNotEmpty()) {
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

        return $stock->load([
            'inventory', 'product', 'batches', 'prices',
            'sellingPrice', 'installmentPrice', 'wholesalePrice',
            'currentQuantity', 'initialQuantity',
        ]);
    }

    public function show(Stock $stock): Stock
    {
        return $stock->loadMissing([
            'inventory', 'product', 'batches', 'prices',
            'sellingPrice', 'installmentPrice', 'wholesalePrice',
            'currentQuantity', 'initialQuantity',
        ]);
    }

    public function delete(Stock $stock): void
    {
        $stock->delete();
    }
}
