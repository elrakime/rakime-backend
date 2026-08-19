<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ContractStatus;
use App\Enums\DrawStatus;
use App\Enums\InstallmentPaymentMethod;
use App\Enums\SubscriptionStatus;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Client;
use App\Models\Draw;
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
                          ->orWhere('note', 'like', "%{$value}%");
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

    public function update(Contract $contract, array $data, bool $isAdmin): Contract
    {
        if (! in_array($contract->status, [ContractStatus::PENDING, ContractStatus::APPROVED], true)) {
            throw new Exception(__('contracts.cannot_update'), 422);
        }

        return DB::transaction(function () use ($contract, $data, $isAdmin) {
            $updates = [];

            if (array_key_exists('max_amount', $data)) {
                if (! $isAdmin) {
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

    private function recalculateAmounts(Contract $contract): void
    {
        $items = $contract->items()->get();

        $totalAmount = $items->isEmpty()
            ? null
            : (int) $items->sum(fn ($item) => $item->quantity * $item->price);

        $advanceAmount = (int) ($contract->advance_amount ?? 0);
        $monthsCount   = $contract->months_count;

        $netAmount = $totalAmount !== null
            ? $totalAmount - $advanceAmount
            : null;

        $monthlyAmount = ($netAmount !== null && $monthsCount > 0)
            ? (int) ceil($netAmount / $monthsCount)
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

        if ($subscriptionCount >= $account->max_withdraw_count) {
            throw new Exception(__('contracts.subscription_count_exceeds_limit'), 422);
        }

        $perDrawAmount = (int) ceil($monthlyAmount / $subscriptionCount);

        if ($perDrawAmount <= $account->min_withdraw_amount) {
            throw new Exception(__('contracts.subscription_amount_below_minimum'), 422);
        }

        return DB::transaction(function () use ($contract, $subscriptionCount, $monthsCount, $monthlyAmount, $perDrawAmount, $drawDay, $drawDate) {
            $installments = [];

            foreach (range(1, $monthsCount) as $monthNumber) {
                $month = Carbon::create($drawDate->year, $drawDate->month + ($monthNumber - 1), 1);

                $installments[] = Installment::create([
                    'contract_id'    => $contract->id,
                    'month_number'   => $monthNumber,
                    'amount'         => $monthlyAmount,
                    'payment_method' => InstallmentPaymentMethod::BANK->value,
                    'due_date'       => $this->resolveDrawDate($month, $drawDay),
                ]);
            }

            foreach (range(1, $subscriptionCount) as $subNumber) {
                $subscription = Subscription::create([
                    'contract_id'         => $contract->id,
                    'reference'           => $contract->reference . '-SUB' . $subNumber,
                    'subscription_number' => $subNumber,
                    'amount'              => $perDrawAmount,
                    'status'              => SubscriptionStatus::ACTIVE,
                ]);

                foreach ($installments as $installment) {
                    Draw::create([
                        'subscription_id' => $subscription->id,
                        'installment_id'  => $installment->id,
                        'month_number'    => $installment->month_number,
                        'amount'          => $perDrawAmount,
                        'status'          => DrawStatus::PENDING->value,
                        'scheduled_date'  => $this->resolveDrawDate(Carbon::parse($installment->due_date), $drawDay),
                    ]);
                }
            }

            $contract->update([
                'status'     => ContractStatus::CONFIGURED,
                'draw_date'  => $drawDate,
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
}
