<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Models\Batch;
use App\Models\InventoryMovement;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class SaleService
{
    public function list(Request $request): LengthAwarePaginator
    {
        return QueryBuilder::for(Sale::class, $request)
            ->with(['user', 'branch', 'client', 'items.product', 'items.stock'])
            ->allowedFilters(
                AllowedFilter::exact('branch_id'),
                AllowedFilter::exact('client_id'),
                AllowedFilter::partial('reference'),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('reference', 'like', "%{$value}%")
                          ->orWhere('note', 'like', "%{$value}%");
                    });
                }),
            )
            ->allowedSorts(
                AllowedSort::field('reference'),
                AllowedSort::field('total_amount'),
                AllowedSort::field('sold_at'),
                AllowedSort::field('created_at'),
            )
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 15))
            ->appends($request->query());
    }

    public function create(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            $totalAmount = collect($data['items'])->sum(fn ($item) => $item['quantity'] * $item['price']);

            $sale = Sale::create([
                'user_id'      => $data['user_id'] ?? auth()->id(),
                'branch_id'    => $data['branch_id'],
                'client_id'    => $data['client_id'] ?? null,
                'reference'    => $this->generateReference('SL'),
                'total_amount' => $totalAmount,
                'note'         => $data['note'] ?? null,
                'sold_at'      => now(),
            ]);

            foreach ($data['items'] as $item) {
                SaleItem::create([
                    'sale_id'    => $sale->id,
                    'product_id' => $item['product_id'],
                    'stock_id'   => $item['stock_id'],
                    'quantity'   => $item['quantity'],
                    'price'      => $item['price'],
                ]);
            }

            $this->deductStock($sale);

            return $sale->load(['user', 'branch', 'client', 'items.product', 'items.stock']);
        });
    }

    public function show(Sale $sale): Sale
    {
        return $sale->loadMissing(['user', 'branch', 'client', 'items.product', 'items.stock']);
    }

    public function update(Sale $sale, array $data): Sale
    {
        $sale->update([
            'branch_id' => $data['branch_id'] ?? $sale->branch_id,
            'client_id' => $data['client_id'] ?? $sale->client_id,
            'note'      => $data['note'] ?? $sale->note,
            'sold_at'   => $data['sold_at'] ?? $sale->sold_at,
        ]);

        return $sale->fresh()->loadMissing(['user', 'branch', 'client', 'items.product', 'items.stock']);
    }

    public function delete(Sale $sale): void
    {
        DB::transaction(function () use ($sale) {
            $sale->items()->delete();
            $sale->inventoryMovements()->delete();
            $sale->delete();
        });
    }

    private function deductStock(Sale $sale): void
    {
        $sale->loadMissing('items');

        foreach ($sale->items as $item) {
            $remaining = $item->quantity;

            $batches = Batch::with('stock')
                ->where('stock_id', $item->stock_id)
                ->where('current_quantity', '>', 0)
                ->orderBy('purchased_at')
                ->get();

            foreach ($batches as $batch) {
                $deduct = min($remaining, $batch->current_quantity);
                $batch->decrement('current_quantity', $deduct);

                InventoryMovement::create([
                    'stock_id'      => $item->stock_id,
                    'batch_id'      => $batch->id,
                    'inventory_id'  => $batch->stock->inventory_id,
                    'product_id'    => $item->product_id,
                    'moveable_id'   => $sale->id,
                    'movement_type' => InventoryMovementType::SALE,
                    'quantity'      => $deduct,
                ]);

                $remaining -= $deduct;
                if ($remaining <= 0) {
                    break;
                }
            }
        }
    }

    private function generateReference(string $prefix): string
    {
        return $prefix . '-' . now()->format('YmdHis') . '-' . strtoupper(substr(uniqid(), -4));
    }
}
