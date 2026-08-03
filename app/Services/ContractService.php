<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ContractStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Draw;
use App\Models\Installment;
use App\Models\Subscription;
use App\Traits\ScopesByUserBranches;
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
    use ScopesByUserBranches;

    public function list(Request $request): LengthAwarePaginator
    {
        $query = Contract::query();

        $this->scopeByUserBranches($query);

        return QueryBuilder::for($query, $request)
            ->with(['client', 'account', 'branch', 'items.product', 'items.stock'])
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

    public function create(array $data): Contract
    {
        $contract = Contract::create([
            'client_id'  => $data['client_id'],
            'account_id' => $data['account_id'],
            'branch_id'  => $data['branch_id'],
            'status'     => ContractStatus::PENDING,
            'note'       => $data['note'] ?? null,
        ]);

        return $contract->load(['client', 'account', 'branch']);
    }

    public function approve(Contract $contract, int $maxAmount): Contract
    {
        if ($contract->status !== ContractStatus::PENDING) {
            throw new Exception(__('contracts.not_pending'), 422);
        }

        $contract->update([
            'status'     => ContractStatus::APPROVED,
            'max_amount' => $maxAmount,
        ]);

        return $contract->fresh(['client', 'account', 'branch']);
    }

    public function reject(Contract $contract): Contract
    {
        if ($contract->status !== ContractStatus::PENDING) {
            throw new Exception(__('contracts.not_pending'), 422);
        }

        $contract->update([
            'status' => ContractStatus::REJECTED,
        ]);

        return $contract->fresh(['client', 'account', 'branch']);
    }

    public function confirm(Contract $contract, array $data): Contract
    {
        if ($contract->status !== ContractStatus::APPROVED) {
            throw new Exception(__('contracts.not_approved'), 422);
        }

        return DB::transaction(function () use ($contract, $data) {
            $contract->items()->delete();

            $totalAmount = 0;
            foreach ($data['items'] as $item) {
                ContractItem::create([
                    'contract_id' => $contract->id,
                    'product_id'  => $item['product_id'],
                    'stock_id'    => $item['stock_id'],
                    'quantity'    => $item['quantity'],
                    'price'       => $item['price'],
                ]);
                $totalAmount += $item['quantity'] * $item['price'];
            }

            $advanceAmount = $data['advance_amount'];
            $monthsCount   = $data['months_count'];
            $monthlyAmount = $monthsCount > 0
                ? (int) ceil(($totalAmount - $advanceAmount) / $monthsCount)
                : 0;

            $reference = $contract->reference
                ?? 'CTR-' . now()->format('Ym') . '-' . str_pad((string) $contract->id, 5, '0', STR_PAD_LEFT);

            $contract->update([
                'reference'      => $reference,
                'advance_amount' => $advanceAmount,
                'months_count'   => $monthsCount,
                'total_amount'   => $totalAmount,
                'monthly_amount' => $monthlyAmount,
                'status'         => ContractStatus::CONFIRMED,
            ]);

            return $contract->fresh(['client', 'account', 'branch', 'items.product', 'items.stock']);
        });
    }

    public function configure(Contract $contract, int $subscriptionCount): Contract
    {
        if ($contract->status !== ContractStatus::CONFIRMED) {
            throw new Exception(__('contracts.not_confirmed'), 422);
        }

        $installments = $contract->installments;
        if ($installments->isEmpty()) {
            throw new Exception(__('contracts.no_installments'), 422);
        }

        $account = $contract->account;
        $drawDay = $account->draw_day;

        $monthlyAmount = $contract->monthly_amount;
        $perDrawAmount = (int) ceil($monthlyAmount / $subscriptionCount);

        return DB::transaction(function () use ($contract, $subscriptionCount, $installments, $perDrawAmount, $drawDay) {
            // Clear any existing subscriptions and draws (re-configuration)
            $contract->subscriptions()->delete();

            // Create subscriptions
            foreach (range(1, $subscriptionCount) as $subNumber) {
                $subscription = Subscription::create([
                    'contract_id'         => $contract->id,
                    'reference'           => $contract->reference . '-SUB' . $subNumber,
                    'subscription_number' => $subNumber,
                    'amount'              => $perDrawAmount,
                    'total_months'        => $contract->months_count,
                    'status'              => SubscriptionStatus::ACTIVE,
                ]);

                // Create draws: one per installment × subscription combination
                foreach ($installments as $installment) {
                    $scheduledDate = Carbon::parse($installment->due_date)->day($drawDay);

                    Draw::create([
                        'subscription_id' => $subscription->id,
                        'installment_id'  => $installment->id,
                        'month_number'    => $installment->month_number,
                        'amount'          => $perDrawAmount,
                        'status'          => 'pending',
                        'scheduled_date'  => $scheduledDate,
                    ]);
                }
            }

            $contract->update([
                'status' => ContractStatus::CONFIGURED,
            ]);

            return $contract->fresh([
                'client', 'account', 'branch',
                'items.product', 'items.stock',
                'installments', 'subscriptions.draws',
            ]);
        });
    }
}
