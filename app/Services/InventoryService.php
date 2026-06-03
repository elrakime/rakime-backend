<?php

namespace App\Services;

use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class InventoryService
{
    public function list(Request $request): Collection
    {
        return QueryBuilder::for(Inventory::class, $request)
            ->with('branch')
            ->allowedFilters(
                AllowedFilter::partial('name'),
                AllowedFilter::exact('branch_id'),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where('name', 'like', "%{$value}%");
                }),
            )
            ->allowedSorts(
                AllowedSort::field('name'),
                AllowedSort::field('created_at'),
            )
            ->defaultSort('-created_at')
            ->get();
    }

    public function create(array $data): Inventory
    {
        $inventory = Inventory::create([
            'branch_id' => $data['branch_id'] ?? null,
            'name'      => $data['name'],
        ]);

        return $inventory->loadMissing('branch');
    }

    public function show(Inventory $inventory): Inventory
    {
        return $inventory->loadMissing('branch');
    }

    public function update(Inventory $inventory, array $data): Inventory
    {
        $inventory->update(array_filter([
            'branch_id' => array_key_exists('branch_id', $data) ? $data['branch_id'] : $inventory->branch_id,
            'name'      => $data['name'] ?? null,
        ], fn ($v) => $v !== null));

        return $inventory->refresh()->loadMissing('branch');
    }

    public function delete(Inventory $inventory): void
    {
        $inventory->delete();
    }
}
