<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Treasury;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class BranchService
{
    public function list(Request $request): Collection
    {
        return QueryBuilder::for(Branch::class, $request)
            ->with('accounts', 'managers')
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
            ->get();
    }

    public function create(array $data): Branch
    {
        $accounts = $data['accounts'] ?? [];

        $branch = Branch::create([
            'name' => $data['name'],
            'code' => $data['code'],
        ]);

        if ($accounts) {
            $branch->accounts()->sync($accounts);
        }

        Inventory::firstOrCreate(
            ['branch_id' => $branch->id],
            ['name' => $branch->name],
        );

        Treasury::firstOrCreate(
            ['branch_id' => $branch->id],
            ['name' => $branch->name, 'balance' => 0],
        );

        return $branch->loadMissing('accounts');
    }

    public function show(Branch $branch): Branch
    {
        return $branch->loadMissing('accounts');
    }

    public function update(Branch $branch, array $data): Branch
    {
        $branch->update(array_filter([
            'name' => $data['name'] ?? null,
            'code' => $data['code'] ?? null,
        ], fn ($v) => $v !== null));

        if (array_key_exists('accounts', $data)) {
            $branch->accounts()->sync($data['accounts'] ?? []);
        }

        Inventory::firstOrCreate(
            ['branch_id' => $branch->id],
            ['name' => $branch->name],
        );

        Treasury::firstOrCreate(
            ['branch_id' => $branch->id],
            ['name' => $branch->name, 'balance' => 0],
        );

        return $branch->refresh()->loadMissing('accounts');
    }

    public function delete(Branch $branch): void
    {
        $branch->delete();
    }
}
