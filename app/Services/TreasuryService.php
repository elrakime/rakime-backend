<?php

namespace App\Services;

use App\Models\Treasury;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class TreasuryService
{
    public function list(Request $request): Collection
    {
        return QueryBuilder::for(Treasury::class, $request)
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
                AllowedSort::field('balance'),
                AllowedSort::field('created_at'),
            )
            ->defaultSort('-created_at')
            ->get();
    }

    public function create(array $data): Treasury
    {
        $treasury = Treasury::create([
            'branch_id' => $data['branch_id'] ?? null,
            'name'      => $data['name'],
            'balance'   => $data['balance'] ?? 0,
        ]);

        return $treasury->loadMissing('branch');
    }

    public function show(Treasury $treasury): Treasury
    {
        return $treasury->loadMissing('branch');
    }

    public function update(Treasury $treasury, array $data): Treasury
    {
        $treasury->update(array_filter([
            'name'    => $data['name'] ?? null,
        ], fn ($v) => $v !== null));

        return $treasury->refresh()->loadMissing('branch');
    }

    public function delete(Treasury $treasury): void
    {
        $treasury->delete();
    }
}
