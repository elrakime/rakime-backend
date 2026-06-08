<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Stock;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class BatchService
{
    public function list(Request $request, Stock $stock): LengthAwarePaginator
    {
        return QueryBuilder::for(Batch::class, $request)
            ->where('stock_id', $stock->id)
            ->allowedFilters(
                AllowedFilter::partial('source_type'),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('source_type', 'like', "%{$value}%");
                    });
                }),
            )
            ->allowedSorts(
                AllowedSort::field('purchase_price'),
                AllowedSort::field('initial_quantity'),
                AllowedSort::field('current_quantity'),
                AllowedSort::field('purchased_at'),
                AllowedSort::field('created_at'),
            )
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 15))
            ->appends($request->query());
    }

    public function create(Stock $stock, array $data): Batch
    {
        return $stock->batches()->create([
            'source_id'        => $data['source_id'] ?? null,
            'source_type'      => $data['source_type'] ?? null,
            'purchase_price'   => $data['purchase_price'],
            'initial_quantity' => $data['initial_quantity'],
            'current_quantity' => $data['current_quantity'] ?? $data['initial_quantity'],
            'purchased_at'     => $data['purchased_at'] ?? now(),
        ]);
    }

    public function show(Batch $batch): Batch
    {
        return $batch->loadMissing(['stock', 'source']);
    }

    public function update(Batch $batch, array $data): Batch
    {
        $batch->update(array_filter([
            'source_id'        => $data['source_id'] ?? null,
            'source_type'      => $data['source_type'] ?? null,
            'purchase_price'   => $data['purchase_price'] ?? null,
            'initial_quantity' => $data['initial_quantity'] ?? null,
            'current_quantity' => $data['current_quantity'] ?? null,
            'purchased_at'     => $data['purchased_at'] ?? null,
        ], fn ($v) => $v !== null));

        return $batch->refresh();
    }

    public function delete(Batch $batch): void
    {
        $batch->delete();
    }
}
