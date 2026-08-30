<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ContractStatus;
use App\Enums\InstallmentPaymentMethod;
use App\Models\Contract;
use App\Models\ContractEarlyCancelation;
use App\Models\ContractItem;
use App\Models\Client;
use App\Models\Installment;
use App\Models\Subscription;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class ContractService
{

    public function list(Request $request): LengthAwarePaginator
    {
        $query = Contract::query();

        $query->byUserBranches();

        return QueryBuilder::for($query, $request)
            ->with(['client', 'account', 'branch', 'items.product', 'items.stock', 'financialRecords'])
            ->allowedFilters(
                AllowedFilter::exact('branch_id'),
                AllowedFilter::exact('client_id'),
                AllowedFilter::exact('status'),
                AllowedFilter::callback('search', function ($query, string $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('reference', 'like', "%{$value}%")
                          ->orWhere('note', 'like', "%{$value}%")
                          ->orWhereHas('client', function ($clientQuery) use ($value) {
                              $clientQuery->where('firstname', 'like', "%{$value}%")
                                  ->orWhere('lastname', 'like', "%{$value}%")
                                  ->orWhere('ccp_number', 'like', "%{$value}%");
                          });
                    });
                }),
            )
            ->allowedSorts(
                AllowedSort::field('reference'),
                AllowedSort::field('total_amount'),
                AllowedSort::field('created_at'),
            )
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 15))
            ->appends($request->query());
    }

    public function show(Contract $contract): Contract
    {
        return $contract->loadMissing([
            'client', 'account', 'branch',
            'items.product',
            'installments',
            'subscriptions.draws',
            'account.drawLocks',
            'financialRecords',
        ]);
    }

    public function create(array $data): Contract
    {
        return DB::transaction(function () use ($data) {
            $client = Client::find($data['client_id']);

            if ($client && $client->is_banned) {
                throw new Exception(__('contracts.client_banned'), 422);
            }

            $contract = Contract::create([
                'client_id'      => $data['client_id'],
                'account_id'     => $data['account_id'],
                'branch_id'      => $data['branch_id'],
                'status'         => ContractStatus::PENDING,
                'advance_amount' => $data['advance_amount'] ?? null,
                'months_count'   => $data['months_count'] ?? null,
                'note'           => $data['note'] ?? null,
            ]);

            if (! empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    ContractItem::create([
                        'contract_id' => $contract->id,
                        'product_id'  => $item['product_id'],
                        'stock_id'    => $item['stock_id'],
                        'quantity'    => $item['quantity'],
                        'price'       => $item['price'],
                    ]);
                }
            }

            $this->recalculateAmounts($contract);

            return $contract->fresh(['client', 'account', 'branch', 'items.product', 'items.stock']);
        });
    }

    public function approve(Contract $contract, ?float $maxAmount = null): Contract
    {
        if ($contract->status !== ContractStatus::PENDING) {
            throw new Exception(__('contracts.not_pending'), 422);
        }

        $contract->update([
            'status'     => ContractStatus::APPROVED,
            'max_amount' => $maxAmount,
        ]);

        return $contract->fresh(['client', 'account', 'branch', 'items.product', 'items.stock']);
    }

    public function reject(Contract $contract, bool $banClient = false): Contract
    {
        if ($contract->status !== ContractStatus::PENDING) {
            throw new Exception(__('contracts.not_pending'), 422);
        }

        return DB::transaction(function () use ($contract, $banClient) {
            $contract->update([
                'status' => ContractStatus::REJECTED,
            ]);

            if ($banClient) {
                $contract->client()->update([
                    'is_banned' => true,
                ]);
            }

            return $contract->fresh(['client', 'account', 'branch']);
        });
    }

    public function update(Contract $contract, array $data): Contract
    {
        $isConfigured = in_array($contract->status, [ContractStatus::CONFIGURED, ContractStatus::ACTIVE], true);

        if ($isConfigured) {
            return $this->updateConfigured($contract, $data);
        }

        if (! in_array($contract->status, [ContractStatus::PENDING, ContractStatus::APPROVED], true)) {
            throw new Exception(__('contracts.cannot_update'), 422);
        }

        return DB::transaction(function () use ($contract, $data) {
            $updates = [];

            if (array_key_exists('max_amount', $data)) {
                if (auth()->user()->isAdmin() === false) {
                    throw new Exception(__('contracts.cannot_update_max_amount'), 403);
                }

                $updates['max_amount'] = $data['max_amount'];
            }

            if (array_key_exists('advance_amount', $data)) {
                $updates['advance_amount'] = $data['advance_amount'];
            }

            if (array_key_exists('months_count', $data)) {
                $updates['months_count'] = $data['months_count'];
            }

            if (array_key_exists('items', $data)) {
                $contract->items()->delete();

                foreach ($data['items'] as $item) {
                    ContractItem::create([
                        'contract_id' => $contract->id,
                        'product_id'  => $item['product_id'],
                        'stock_id'    => $item['stock_id'],
                        'quantity'    => $item['quantity'],
                        'price'       => $item['price'],
                    ]);
                }
            }

            if ($updates !== []) {
                $contract->update($updates);
            }

            if (
                array_key_exists('items', $data)
                || array_key_exists('advance_amount', $data)
                || array_key_exists('months_count', $data)
            ) {
                $this->recalculateAmounts($contract);
            }

            $maxAmount = $updates['max_amount'] ?? $contract->max_amount;

            if ($maxAmount !== null && $contract->net_amount !== null && $contract->net_amount > $maxAmount) {
                throw new Exception(__('contracts.net_exceeds_max_amount'), 422);
            }

            return $contract->fresh(['client', 'account', 'branch', 'items.product', 'items.stock']);
        });
    }

    /**
     * Update a configured/active contract (admin only).
     *
     * Allows changing items, advance_amount and max_amount. The months_count
     * and subscription count are preserved; amounts are recalculated and
     * propagated to the existing installments and subscriptions.
     */
    private function updateConfigured(Contract $contract, array $data): Contract
    {
        if (auth()->user()->isAdmin() === false) {
            throw new Exception(__('contracts.cannot_update'), 403);
        }

        if (array_key_exists('months_count', $data)) {
            throw new Exception(__('contracts.cannot_update_months_count'), 422);
        }

        return DB::transaction(function () use ($contract, $data) {
            $updates = [];

            if (array_key_exists('advance_amount', $data)) {
                $updates['advance_amount'] = $data['advance_amount'];
            }

            if (array_key_exists('max_amount', $data)) {
                $updates['max_amount'] = $data['max_amount'];
            }

            if (array_key_exists('items', $data)) {
                $contract->items()->delete();

                foreach ($data['items'] as $item) {
                    ContractItem::create([
                        'contract_id' => $contract->id,
                        'product_id'  => $item['product_id'],
                        'stock_id'    => $item['stock_id'],
                        'quantity'    => $item['quantity'],
                        'price'       => $item['price'],
                    ]);
                }
            }

            if ($updates !== []) {
                $contract->update($updates);
            }

            if (
                array_key_exists('items', $data)
                || array_key_exists('advance_amount', $data)
            ) {
                $this->recalculateAmounts($contract);
                $this->recalculateInstallmentsAndSubscriptions($contract);
            }

            $maxAmount = $updates['max_amount'] ?? $contract->max_amount;

            if ($maxAmount !== null && $contract->net_amount !== null && $contract->net_amount > $maxAmount) {
                throw new Exception(__('contracts.net_exceeds_max_amount'), 422);
            }

            return $contract->fresh([
                'client', 'account', 'branch',
                'items.product', 'items.stock',
                'installments', 'subscriptions.draws',
            ]);
        });
    }

    /**
     * Propagate the recalculated monthly amount to the existing installments
     * and subscriptions (their counts are preserved).
     */
    private function recalculateInstallmentsAndSubscriptions(Contract $contract): void
    {
        $monthlyAmount = (float) $contract->monthly_amount;

        $subscriptionCount = $contract->subscriptions()->count();

        $perDrawAmount = $subscriptionCount > 0
            ? (float) ceil($monthlyAmount / $subscriptionCount)
            : 0.0;

        $contract->installments()->update([
            'amount' => $monthlyAmount,
        ]);

        $contract->subscriptions()->update([
            'amount' => $perDrawAmount,
        ]);
    }

    private function recalculateAmounts(Contract $contract): void
    {
        $items = $contract->items()->get();

        $totalAmount = $items->isEmpty()
            ? null
            : (float) $items->sum(fn ($item) => $item->quantity * $item->price);

        $advanceAmount = (float) ($contract->advance_amount ?? 0);
        $monthsCount   = $contract->months_count;

        $netAmount = $totalAmount !== null
            ? $totalAmount - $advanceAmount
            : null;

        $monthlyAmount = ($netAmount !== null && $monthsCount > 0)
            ? (float) ceil($netAmount / $monthsCount)
            : null;

        $contract->update([
            'total_amount'   => $totalAmount,
            'net_amount'     => $netAmount,
            'monthly_amount' => $monthlyAmount,
        ]);
    }

    public function configure(Contract $contract, int $subscriptionCount, string $drawDate): Contract
    {
        if ($contract->status !== ContractStatus::APPROVED) {
            throw new Exception(__('contracts.not_approved'), 422);
        }

        $monthlyAmount = $contract->monthly_amount;
        $monthsCount   = $contract->months_count;

        if ($monthlyAmount === null || $monthsCount === null || $monthsCount < 1) {
            throw new Exception(__('contracts.missing_terms'), 422);
        }

        $account = $contract->account;
        $drawDay = $account->draw_day;

        $drawDate = Carbon::parse($drawDate)->startOfDay();

        if ($drawDate->day !== min($drawDay, $drawDate->daysInMonth)) {
            throw new Exception(__('contracts.draw_date_invalid'), 422);
        }

        $lastLock = $account->drawLocks()->latest('month')->first();

        if ($lastLock !== null && $drawDate->lte(Carbon::parse($lastLock->month)->startOfDay())) {
            throw new Exception(__('contracts.draw_date_before_lock'), 422);
        }

        if ($subscriptionCount > $account->max_withdraw_count) {
            throw new Exception(__('contracts.subscription_count_exceeds_limit'), 422);
        }

        $perDrawAmount = (float) ceil($monthlyAmount / $subscriptionCount);
        $perDrawAmount = (float) ceil($monthlyAmount / $subscriptionCount);

        if ($perDrawAmount < $account->min_withdraw_amount) {
            throw new Exception(__('contracts.subscription_amount_below_minimum'), 422);
        }

        return DB::transaction(function () use ($contract, $subscriptionCount, $monthsCount, $monthlyAmount, $perDrawAmount, $drawDay, $drawDate) {
            $firstDueDate = null;
            $lastDueDate  = null;

            foreach (range(1, $monthsCount) as $monthNumber) {
                $month = Carbon::create($drawDate->year, $drawDate->month + ($monthNumber - 1), 1);

                $dueDate = $this->resolveDrawDate($month, $drawDay);

                Installment::create([
                    'contract_id'    => $contract->id,
                    'amount'         => $monthlyAmount,
                    'payment_method' => InstallmentPaymentMethod::BANK->value,
                    'due_date'       => $dueDate,
                ]);

                $firstDueDate ??= $dueDate;
                $lastDueDate = $dueDate;
            }

            foreach (range(1, $subscriptionCount) as $subNumber) {
                // Reference is generated by Subscription::booted() (padded format).
                Subscription::create([
                    'contract_id'         => $contract->id,
                    'subscription_number' => $subNumber,
                    'amount'              => $perDrawAmount,
                ]);
            }

            $contract->update([
                'status'     => ContractStatus::CONFIGURED,
                'start_date' => $firstDueDate,
                'end_date'   => $lastDueDate,
            ]);

            return $contract->fresh([
                'client', 'account', 'branch',
                'items.product', 'items.stock',
                'installments', 'subscriptions.draws',
            ]);
        });
    }

    private function resolveDrawDate(Carbon $month, int $drawDay): Carbon
    {
        $date = $month->copy()->startOfMonth();
        $day  = min($drawDay, $date->daysInMonth);

        return $date->setDay($day);
    }

    /**
     * Cancel a configured/active contract, triggering an early cancellation
     * scheduled on the account's next draw day.
     */
    public function cancel(Contract $contract): Contract
    {
        if (! in_array($contract->status, [ContractStatus::CONFIGURED, ContractStatus::ACTIVE], true)) {
            throw new Exception(__('contracts.cannot_cancel'), 422);
        }

        return DB::transaction(function () use ($contract) {
            $contract->update([
                'status' => ContractStatus::CANCELLED,
            ]);

            if ($contract->subscriptions()->exists()) {
                ContractEarlyCancelation::create([
                    'contract_id' => $contract->id,
                    'payment_id'  => null,
                    'end_date'    => $this->resolveNextDrawDate($contract),
                ]);
            }

            return $contract->fresh([
                'client', 'account', 'branch',
                'items.product', 'items.stock',
                'installments', 'subscriptions.draws',
                'earlyCancelations',
            ]);
        });
    }

    /**
     * Resolve the account's next draw day (the next occurrence of draw_day).
     */
    private function resolveNextDrawDate(Contract $contract): Carbon
    {
        $account = $contract->account;

        $today = now()->startOfDay();
        $day   = min($account->draw_day, $today->daysInMonth);

        $drawDate = $today->copy()->startOfMonth()->addDays($day - 1);

        if ($drawDate->lt($today)) {
            $drawDate = $today->copy()->addMonth()->startOfMonth();
            $day      = min($account->draw_day, $drawDate->daysInMonth);
            $drawDate->addDays($day - 1);
        }

        return $drawDate;
    }
}
