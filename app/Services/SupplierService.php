<?php

namespace App\Services;

use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class SupplierService
{
    public function list(Request $request): LengthAwarePaginator
    {
        return QueryBuilder::for(Supplier::class, $request)
            ->with('wilaya')
            ->allowedFilters(
                AllowedFilter::partial('name'),
                AllowedFilter::partial('phone'),
                AllowedFilter::exact('is_active'),
                AllowedFilter::exact('wilaya_id'),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('name', 'like', "%{$value}%")
                          ->orWhere('phone', 'like', "%{$value}%")
                          ->orWhere('email', 'like', "%{$value}%");
                    });
                }),
            )
            ->allowedSorts(
                AllowedSort::field('name'),
                AllowedSort::field('created_at'),
            )
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 15))
            ->appends($request->query());
    }

    public function create(array $data, Request $request): Supplier
    {
        $supplier = Supplier::create(collect($data)->except('image')->toArray());

        if ($request->hasFile('image')) {
            $supplier->addMediaFromRequest('image')->toMediaCollection('image');
        }

        return $supplier;
    }

    public function show(Supplier $supplier): Supplier
    {
        return $supplier->loadMissing('wilaya');
    }

    public function update(Supplier $supplier, array $data, Request $request): Supplier
    {
        $supplier->update(collect($data)->except('image')->toArray());

        if ($request->hasFile('image')) {
            $supplier->clearMediaCollection('image');
            $supplier->addMediaFromRequest('image')->toMediaCollection('image');
        }

        return $supplier->refresh();
    }

    public function delete(Supplier $supplier): void
    {
        $supplier->delete();
    }
}
