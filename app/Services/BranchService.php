<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class BranchService
{
    public function list(Request $request): Collection
    {
        $query = Branch::query();

        $query->byUserBranches();

        return QueryBuilder::for($query, $request)
            ->with('accounts', 'managers', 'wilaya')
            ->allowedFilters(
                AllowedFilter::partial('name'),
                AllowedFilter::partial('code'),
                AllowedFilter::partial('shop_name'),
                AllowedFilter::partial('phone'),
                AllowedFilter::exact('wilaya_id'),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('name', 'like', "%{$value}%")
                          ->orWhere('code', 'like', "%{$value}%")
                          ->orWhere('shop_name', 'like', "%{$value}%")
                          ->orWhere('address', 'like', "%{$value}%")
                          ->orWhere('phone', 'like', "%{$value}%");
                    });
                }),
            )
            ->allowedSorts(
                AllowedSort::field('name'),
                AllowedSort::field('code'),
                AllowedSort::field('shop_name'),
                AllowedSort::field('created_at'),
            )
            ->defaultSort('-created_at')
            ->get();
    }

    public function create(array $data, Request $request): Branch
    {
        $accounts = $data['accounts'] ?? [];

        $branch = Branch::create([
            'name'      => $data['name'],
            'code'      => $data['code'],
            'shop_name' => $data['shop_name'],
            'address'   => $data['address'] ?? null,
            'phone'     => $data['phone'] ?? null,
            'wilaya_id' => $data['wilaya_id'] ?? null,
            'metadata'  => $data['metadata'] ?? null,
        ]);

        if ($accounts) {
            $branch->accounts()->sync($accounts);
        }

        if ($request->hasFile('image')) {
            $branch->addMediaFromRequest('image')->toMediaCollection('image');
        }

        Inventory::firstOrCreate(
            ['branch_id' => $branch->id],
            ['name' => $branch->name],
        );

        Wallet::firstOrCreate(
            ['owner_type' => Branch::class, 'owner_id' => $branch->id],
            ['name' => $branch->name, 'balance' => 0],
        );

        return $branch->loadMissing('accounts');
    }

    public function show(Branch $branch): Branch
    {
        return $branch->loadMissing(['accounts', 'wilaya']);
    }

    public function update(Branch $branch, array $data, Request $request): Branch
    {
        $branch->update(array_filter([
            'name'      => $data['name'] ?? null,
            'code'      => $data['code'] ?? null,
            'shop_name' => $data['shop_name'] ?? null,
            'address'   => $data['address'] ?? null,
            'phone'     => $data['phone'] ?? null,
            'wilaya_id' => $data['wilaya_id'] ?? null,
            'metadata'  => $data['metadata'] ?? null,
        ], fn ($v) => $v !== null));

        if (array_key_exists('accounts', $data)) {
            $branch->accounts()->sync($data['accounts'] ?? []);
        }

        if ($request->hasFile('image')) {
            $branch->clearMediaCollection('image');
            $branch->addMediaFromRequest('image')->toMediaCollection('image');
        }

        Inventory::firstOrCreate(
            ['branch_id' => $branch->id],
            ['name' => $branch->name],
        );

        Wallet::firstOrCreate(
            ['owner_type' => Branch::class, 'owner_id' => $branch->id],
            ['name' => $branch->name, 'balance' => 0],
        );

        return $branch->refresh()->loadMissing('accounts');
    }

    public function delete(Branch $branch): void
    {
        $branch->delete();
    }
}
