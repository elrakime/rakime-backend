<?php

namespace App\Services;

use App\Models\Brand;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class BrandService
{
    public function list(Request $request): LengthAwarePaginator
    {
        return QueryBuilder::for(Brand::class, $request)
            ->allowedFilters(
                AllowedFilter::partial('name'),
            )
            ->allowedSorts(
                AllowedSort::field('name'),
                AllowedSort::field('created_at'),
            )
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 15))
            ->appends($request->query());
    }

    public function create(array $data, Request $request): Brand
    {
        $brand = Brand::create(['name' => $data['name']]);

        if ($request->hasFile('image')) {
            $brand->addMediaFromRequest('image')->toMediaCollection('image');
        }

        return $brand;
    }

    public function show(Brand $brand): Brand
    {
        return $brand;
    }

    public function update(Brand $brand, array $data, Request $request): Brand
    {
        $brand->update(['name' => $data['name']]);

        if ($request->hasFile('image')) {
            $brand->clearMediaCollection('image');
            $brand->addMediaFromRequest('image')->toMediaCollection('image');
        }

        return $brand->refresh();
    }

    public function delete(Brand $brand): void
    {
        $brand->delete();
    }
}
