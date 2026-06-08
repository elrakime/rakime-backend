<?php

namespace App\Services;

use App\Models\Price;
use App\Models\Stock;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class PriceService
{
    public function list(Request $request, Stock $stock): LengthAwarePaginator
    {
        return QueryBuilder::for(Price::class, $request)
            ->where('stock_id', $stock->id)
            ->allowedFilters(
                AllowedFilter::partial('type'),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('type', 'like', "%{$value}%");
                    });
                }),
            )
            ->allowedSorts(
                AllowedSort::field('type'),
                AllowedSort::field('amount'),
                AllowedSort::field('created_at'),
            )
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 15))
            ->appends($request->query());
    }

    public function create(Stock $stock, array $data): Price
    {
        return $stock->prices()->create([
            'type'     => $data['type'],
            'amount'   => $data['amount'],
        ]);
    }

    public function show(Price $price): Price
    {
        return $price->loadMissing('stock');
    }

    public function update(Price $price, array $data): Price
    {
        $price->update(array_filter([
            'type'     => $data['type'] ?? null,
            'amount'   => $data['amount'] ?? null,
        ], fn ($v) => $v !== null));

        return $price->refresh();
    }

    public function delete(Price $price): void
    {
        $price->delete();
    }
}
