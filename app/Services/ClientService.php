<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class ClientService
{
    public function list(Request $request): LengthAwarePaginator
    {
        $query = Client::query();

        $query->byUserBranches();

        return QueryBuilder::for($query, $request)
            ->with(['branch', 'wilaya'])
            ->allowedFilters(
                AllowedFilter::partial('firstname'),
                AllowedFilter::partial('lastname'),
                AllowedFilter::partial('phone'),
                AllowedFilter::partial('nin'),
                AllowedFilter::partial('ccp_number'),
                AllowedFilter::exact('branch_id'),
                AllowedFilter::exact('wilaya_id'),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('firstname', 'like', "%{$value}%")
                          ->orWhere('lastname', 'like', "%{$value}%")
                          ->orWhere('phone', 'like', "%{$value}%")
                          ->orWhere('nin', 'like', "%{$value}%")
                          ->orWhere('ccp_number', 'like', "%{$value}%");
                    });
                }),
            )
            ->allowedSorts(
                AllowedSort::field('firstname'),
                AllowedSort::field('lastname'),
                AllowedSort::field('phone'),
                AllowedSort::field('created_at'),
            )
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 15))
            ->appends($request->query());
    }

    public function create(array $data, Request $request): Client
    {
        $client = Client::create(collect($data)->except('image')->toArray());

        if ($request->hasFile('image')) {
            $client->addMediaFromRequest('image')->toMediaCollection('image');
        }

        return $client;
    }

    public function show(Client $client): Client
    {
        return $client->loadMissing(['branch', 'wilaya']);
    }

    public function update(Client $client, array $data, Request $request): Client
    {
        $client->update(collect($data)->except('image')->toArray());

        if ($request->hasFile('image')) {
            $client->clearMediaCollection('image');
            $client->addMediaFromRequest('image')->toMediaCollection('image');
        }

        return $client->refresh()->loadMissing(['branch', 'wilaya']);
    }

    public function delete(Client $client): void
    {
        $client->delete();
    }
}
