<?php

namespace App\Services;

use App\Enums\DrawStatus;
use App\Models\Client;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
            ->with(['branch', 'wilaya', 'financialRecords'])
            ->allowedFilters(
                AllowedFilter::partial('firstname'),
                AllowedFilter::partial('lastname'),
                AllowedFilter::partial('phone'),
                AllowedFilter::partial('nin'),
                AllowedFilter::partial('ccp_number'),
                AllowedFilter::exact('branch_id'),
                AllowedFilter::exact('wilaya_id'),
                AllowedFilter::exact('is_banned'),
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

    /**
     * List delinquent clients — those with postponed or failed draws.
     *
     * A client is considered delinquent for a given month only when an installment
     * has a postponed/failed draw in that month AND that same installment was not
     * settled (paid_on_time or late_payment) in the same month.
     *
     * Optional filters:
     *  - account_id: only consider draws belonging to contracts of this account.
     *  - date: only consider draws whose due_date falls within the given month/year
     *          (format: Y-m, YYYY-MM, or YYYY-MM-DD — only month and year are relevant).
     */
    public function delinquent(Request $request): LengthAwarePaginator
    {
        $query = Client::query();

        $query->byUserBranches();

        $date = $request->input('date') ?? now();
        try {
            $parsed = Carbon::parse($date);
        } catch (Exception) {
            $parsed = now();
        }

        $query->whereHas('installmentContracts.subscriptions.draws', function ($draw) use ($parsed) {
            $draw->whereIn('status', [DrawStatus::POSTPONED, DrawStatus::FAILED])
                ->whereYear('due_date', $parsed->year)
                ->whereMonth('due_date', $parsed->month);

            // Exclude installments that were also settled in the same month.
            $draw->whereDoesntHave('installment.draws', function ($settled) use ($parsed) {
                $settled->whereIn('status', [DrawStatus::PAID_ON_TIME, DrawStatus::LATE_PAYMENT])
                    ->whereYear('due_date', $parsed->year)
                    ->whereMonth('due_date', $parsed->month);
            });
        });

        $accountId = $request->input('account_id');
        if ($accountId !== null) {
            $query->whereHas('installmentContracts', function ($contract) use ($accountId, $parsed) {
                $contract->where('account_id', $accountId)
                    ->whereHas('subscriptions.draws', function ($draw) use ($parsed) {
                        $draw->whereIn('status', [DrawStatus::POSTPONED, DrawStatus::FAILED])
                            ->whereYear('due_date', $parsed->year)
                            ->whereMonth('due_date', $parsed->month);

                        $draw->whereDoesntHave('installment.draws', function ($settled) use ($parsed) {
                            $settled->whereIn('status', [DrawStatus::PAID_ON_TIME, DrawStatus::LATE_PAYMENT])
                                ->whereYear('due_date', $parsed->year)
                                ->whereMonth('due_date', $parsed->month);
                        });
                    });
            });
        }

        return $query
            ->with(['branch', 'wilaya'])
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15))
            ->appends($request->query());
    }

    public function find(string $keyword): ?Client
    {
        return Client::query()
            ->where('ccp_number', $keyword)
            ->orWhere('nin', $keyword)
            ->with(['branch', 'wilaya', 'financialRecords'])
            ->first();
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
        return $client->loadMissing(['branch', 'wilaya', 'financialRecords']);
    }

    public function update(Client $client, array $data, Request $request): Client
    {
        $updates = $request->except('image','is_banned');

        if (array_key_exists('is_banned', $data)) {
                if (auth()->user()->isAdmin() === false) {
                    throw new Exception(__('clients.cannot_update_is_banned'), 403);
                }

                $updates['is_banned'] = $data['is_banned'];
            }

        $client->update($updates);

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
