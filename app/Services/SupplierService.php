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
            ->allowedFilters(
                AllowedFilter::partial('name'),
                AllowedFilter::partial('phone'),
                AllowedFilter::exact('is_active'),
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

    public function create(array $data): Supplier
    {
        return Supplier::create($data);
    }

    public function show(Supplier $supplier): Supplier
    {
        return $supplier;
    }

    public function update(Supplier $supplier, array $data): Supplier
    {
        $supplier->update($data);

        return $supplier->refresh();
    }

    public function delete(Supplier $supplier): void
    {
        $supplier->delete();
    }
}
