<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountDrawLock;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class AccountDrawLockService
{
    public function list(Request $request): LengthAwarePaginator
    {
        return QueryBuilder::for(AccountDrawLock::class, $request)
            ->with('account')
            ->allowedFilters(
                AllowedFilter::exact('account_id'),
            )
            ->allowedSorts(
                AllowedSort::field('month'),
                AllowedSort::field('created_at'),
            )
            ->defaultSort('-month')
            ->paginate($request->integer('per_page', 15))
            ->appends($request->query());
    }

    public function create(array $data): AccountDrawLock
    {
        $account = Account::findOrFail($data['account_id']);

        $month = now()->startOfMonth();
        $day   = min($account->draw_day, $month->daysInMonth);
        $lockDate = $month->copy()->addDays($day - 1);

        $alreadyLocked = AccountDrawLock::where('account_id', $account->id)
            ->where('month', $lockDate->toDateString())
            ->exists();

        if ($alreadyLocked) {
            throw new \Exception(__('account_draw_locks.already_locked'), 422);
        }

        return AccountDrawLock::create([
            'account_id' => $account->id,
            'month'      => $lockDate,
        ]);
    }
}
