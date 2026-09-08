<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class ProductService
{
    public function list(Request $request): LengthAwarePaginator
    {
        return QueryBuilder::for(Product::class, $request)
            ->with(['type', 'color', 'brand'])
            ->allowedFilters(
                AllowedFilter::partial('name'),
                AllowedFilter::partial('barcode'),
                AllowedFilter::exact('type_id'),
                AllowedFilter::exact('color_id'),
                AllowedFilter::exact('brand_id'),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('name', 'like', "%{$value}%")
                          ->orWhere('barcode', 'like', "%{$value}%");
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

    public function create(array $data, Request $request): Product
    {
        $product = Product::create([
            'type_id'      => $data['type_id'],
            'color_id'     => $data['color_id'],
            'brand_id'     => $data['brand_id'],
            'name'         => $data['name'],
            'barcode'      => $data['barcode'] ?? null,
            'image'        => '',
            'min_quantity' => $data['min_quantity'],
        ]);

        if ($request->hasFile('image')) {
            $media = $product->addMediaFromRequest('image')->toMediaCollection('image');
            $product->update(['image' => $media->getUrl()]);
        }

        return $product->load(['type', 'color', 'brand']);
    }

    public function show(Product $product): Product
    {
        return $product->loadMissing(['type', 'color', 'brand']);
    }

    public function update(Product $product, array $data, Request $request): Product
    {
        $product->update(array_filter([
            'type_id'      => $data['type_id'] ?? null,
            'color_id'     => $data['color_id'] ?? null,
            'brand_id'     => $data['brand_id'] ?? null,
            'name'         => $data['name'] ?? null,
            'barcode'      => array_key_exists('barcode', $data) ? $data['barcode'] : null,
            'min_quantity' => $data['min_quantity'] ?? null,
        ], fn ($v) => $v !== null));

        if ($request->hasFile('image')) {
            $product->clearMediaCollection('image');
            $product->addMediaFromRequest('image')->toMediaCollection('image');
        }

        return $product->refresh()->loadMissing(['type', 'color', 'brand']);
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }
}
