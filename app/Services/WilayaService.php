<?php

namespace App\Services;

use App\Models\Wilaya;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class WilayaService
{
    public function list(Request $request): Collection
    {
        return QueryBuilder::for(Wilaya::class, $request)
            ->allowedFilters(
                AllowedFilter::partial('name'),
                AllowedFilter::callback('created_at_from', function ($query, string $value) {
                    $query->whereDate('created_at', '>=', $value);
                }),
                AllowedFilter::callback('created_at_to', function ($query, string $value) {
                    $query->whereDate('created_at', '<=', $value);
                }),
            )
            ->allowedSorts(
                AllowedSort::field('name'),
                AllowedSort::field('created_at'),
            )
            ->defaultSort('name')
            ->get();
    }

    public function create(array $data): Wilaya
    {
        return Wilaya::create(['name' => $data['name']]);
    }

    public function show(Wilaya $wilaya): Wilaya
    {
        return $wilaya;
    }

    public function update(Wilaya $wilaya, array $data): Wilaya
    {
        $wilaya->update(['name' => $data['name']]);

        return $wilaya->refresh();
    }

    public function delete(Wilaya $wilaya): void
    {
        $wilaya->delete();
    }
}
