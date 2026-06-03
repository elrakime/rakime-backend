<?php

namespace App\Services;

use App\Models\Color;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class ColorService
{
    public function list(Request $request): LengthAwarePaginator
    {
        return QueryBuilder::for(Color::class, $request)
            ->allowedFilters(
                AllowedFilter::partial('name'),
                AllowedFilter::partial('code'),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('name', 'like', "%{$value}%")
                          ->orWhere('code', 'like', "%{$value}%");
                    });
                }),
            )
            ->allowedSorts(
                AllowedSort::field('name'),
                AllowedSort::field('code'),
                AllowedSort::field('created_at'),
            )
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 15))
            ->appends($request->query());
    }

    public function create(array $data): Color
    {
        return Color::create([
            'name' => $data['name'],
            'code' => $data['code'],
        ]);
    }

    public function show(Color $color): Color
    {
        return $color;
    }

    public function update(Color $color, array $data): Color
    {
        $color->update(array_filter([
            'name' => $data['name'] ?? null,
            'code' => $data['code'] ?? null,
        ], fn ($v) => $v !== null));

        return $color->refresh();
    }

    public function delete(Color $color): void
    {
        $color->delete();
    }
}
