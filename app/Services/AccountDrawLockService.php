<?php

namespace App\Services;

use App\Enums\ContractStatus;
use App\Models\Account;
use App\Models\AccountDrawLock;
use App\Models\Contract;
use App\Models\StatusHistory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                AllowedFilter::callback('created_at_from', function ($query, string $value) {
                    $query->whereDate('created_at', '>=', $value);
                }),
                AllowedFilter::callback('created_at_to', function ($query, string $value) {
                    $query->whereDate('created_at', '<=', $value);
                }),
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

        $today = now()->startOfDay();
        $day   = min($account->draw_day, $today->daysInMonth);

        $lockDate = $today->copy()->startOfMonth()->addDays($day - 1);

        if ($lockDate->lt($today)) {
            $lockDate = $today->copy()->addMonth()->startOfMonth();
            $day      = min($account->draw_day, $lockDate->daysInMonth);
            $lockDate->addDays($day - 1);
        }

        $alreadyLocked = AccountDrawLock::where('account_id', $account->id)
            ->where('month', $lockDate->toDateString())
            ->exists();

        if ($alreadyLocked) {
            throw new \Exception(__('account_draw_locks.already_locked'), 422);
        }

        return DB::transaction(function () use ($account, $lockDate) {
            $lock = AccountDrawLock::create([
                'account_id' => $account->id,
                'month'      => $lockDate,
            ]);

            $contractIds = Contract::query()
                ->where('account_id', $account->id)
                ->where('status', ContractStatus::CONFIGURED)
                ->where(function ($query) use ($lockDate) {
                    $query->whereHas('installments', function ($q) use ($lockDate) {
                        $q->whereDate('due_date', $lockDate);
                    })
                    ->orWhereDate('start_date', $lockDate);
                })
                ->pluck('id');

            if ($contractIds->isNotEmpty()) {
                Contract::whereIn('id', $contractIds)
                    ->update(['status' => ContractStatus::ACTIVE]);

                StatusHistory::insert(
                    $contractIds
                        ->map(fn (int $id) => [
                            'statusable_type' => Contract::class,
                            'statusable_id'   => $id,
                            'from_status'     => ContractStatus::CONFIGURED->value,
                            'to_status'       => ContractStatus::ACTIVE->value,
                            'changed_by'      => auth()->id(),
                            'changed_at'      => now(),
                        ])
                        ->all()
                );
            }

            return $lock;
        });
    }
}
