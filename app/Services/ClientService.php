<?php

namespace App\Services;

use App\Models\Client;
use App\Traits\ScopesByUserBranches;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class ClientService
{
    use ScopesByUserBranches;
    public function list(Request $request): LengthAwarePaginator
    {
        $query = Client::query();

        $this->scopeByUserBranches($query);

        return QueryBuilder::for($query, $request)
            ->with(['branch', 'wilaya'])
            ->allowedFilters(
                AllowedFilter::partial('firstname'),
                AllowedFilter::partial('lastname'),
                AllowedFilter::partial('phone'),
                AllowedFilter::partial('nin'),
                AllowedFilter::exact('branch_id'),
                AllowedFilter::exact('wilaya_id'),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('firstname', 'like', "%{$value}%")
                          ->orWhere('lastname', 'like', "%{$value}%")
                          ->orWhere('phone', 'like', "%{$value}%")
                          ->orWhere('nin', 'like', "%{$value}%");
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

    public function create(array $data): Client
    {
        return Client::create($data);
    }

    public function show(Client $client): Client
    {
        return $client->loadMissing(['branch', 'wilaya']);
    }

    public function update(Client $client, array $data): Client
    {
        $client->update($data);

        return $client->refresh()->loadMissing(['branch', 'wilaya']);
    }

    public function delete(Client $client): void
    {
        $client->delete();
    }
}
