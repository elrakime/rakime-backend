<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class CategoryService
{
    public function list(Request $request): LengthAwarePaginator
    {
        return QueryBuilder::for(Category::class, $request)
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

    public function create(array $data, Request $request): Category
    {
        $category = Category::create(['name' => $data['name']]);

        if ($request->hasFile('image')) {
            $category->addMediaFromRequest('image')->toMediaCollection('image');
        }

        return $category;
    }

    public function show(Category $category): Category
    {
        return $category;
    }

    public function update(Category $category, array $data, Request $request): Category
    {
        $category->update(['name' => $data['name']]);

        if ($request->hasFile('image')) {
            $category->clearMediaCollection('image');
            $category->addMediaFromRequest('image')->toMediaCollection('image');
        }

        return $category->refresh();
    }

    public function delete(Category $category): void
    {
        $category->delete();
    }
}
