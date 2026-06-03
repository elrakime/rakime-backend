<?php

namespace App\Services;

use App\Models\Type;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class TypeService
{
    public function list(Request $request): LengthAwarePaginator
    {
        return QueryBuilder::for(Type::class, $request)
            ->with('category')
            ->allowedFilters(
                AllowedFilter::partial('name'),
                AllowedFilter::exact('category_id'),
            )
            ->allowedSorts(
                AllowedSort::field('name'),
                AllowedSort::field('created_at'),
            )
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 15))
            ->appends($request->query());
    }

    public function create(array $data): Type
    {
        return Type::create([
            'category_id' => $data['category_id'],
            'name'        => $data['name'],
        ])->loadMissing('category');
    }

    public function show(Type $type): Type
    {
        return $type->loadMissing('category');
    }

    public function update(Type $type, array $data): Type
    {
        $type->update(array_filter([
            'category_id' => $data['category_id'] ?? null,
            'name'        => $data['name'] ?? null,
        ], fn ($v) => $v !== null));

        return $type->refresh()->loadMissing('category');
    }

    public function delete(Type $type): void
    {
        $type->delete();
    }
}
