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
        $query = Batch::query();

        $query->byUserBranches();

        return QueryBuilder::for($query, $request)
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
                AllowedSort::field('created_at'),
            )
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 15))
            ->appends($request->query());
    }

    public function create(Stock $stock, array $data): Batch
    {
        $oldQuantity = $this->stockCurrentQuantity($stock);

        $batch = $stock->batches()->create([
            'source_id'        => $data['source_id'] ?? null,
            'source_type'      => $data['source_type'] ?? null,
            'purchase_price'   => $data['purchase_price'],
            'initial_quantity' => $data['initial_quantity'],
            'current_quantity' => $data['current_quantity'] ?? $data['initial_quantity'],
        ]);

        $quantity = (int) $batch->current_quantity;

        if ($quantity > 0) {
            app(InventoryService::class)->manual(
                $stock->id,
                $stock->inventory_id,
                $stock->product_id,
                $oldQuantity,
                $quantity,
                $batch,
                [[
                    'batch_id'       => $batch->id,
                    'quantity'       => $quantity,
                    'purchase_price' => $batch->purchase_price,
                ]],
            );
        }

        return $batch;
    }

    public function show(Batch $batch): Batch
    {
        return $batch->loadMissing(['stock', 'source']);
    }

    public function update(Batch $batch, array $data): Batch
    {
        $stock = $batch->stock;

        $oldStockQuantity = $this->stockCurrentQuantity($stock);
        $oldQuantity      = (int) $batch->current_quantity;

        $batch->update(array_filter([
            'source_id'        => $data['source_id'] ?? null,
            'source_type'      => $data['source_type'] ?? null,
            'purchase_price'   => $data['purchase_price'] ?? null,
            'initial_quantity' => $data['initial_quantity'] ?? null,
            'current_quantity' => $data['current_quantity'] ?? null,
        ], fn ($v) => $v !== null));

        $batch->refresh();

        $newQuantity = (int) $batch->current_quantity;
        $difference  = $newQuantity - $oldQuantity;

        if ($difference !== 0) {
            app(InventoryService::class)->manual(
                $stock->id,
                $stock->inventory_id,
                $stock->product_id,
                $oldStockQuantity,
                $difference,
                $batch,
                [[
                    'batch_id'       => $batch->id,
                    'quantity'       => $difference,
                    'purchase_price' => $batch->purchase_price,
                ]],
            );
        }

        return $batch;
    }

    private function stockCurrentQuantity(Stock $stock): int
    {
        return (int) $stock->batches()->sum('current_quantity');
    }

    public function delete(Batch $batch): void
    {
        $batch->delete();
    }
}
