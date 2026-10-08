<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DrawStatus;
use App\Enums\InstallmentStatus;
use App\Models\Branch;
use App\Models\Contract;
use App\Models\ContractStats;
use App\Models\Draw;
use App\Models\Installment;
use App\Traits\HasStatsFilters;
use Carbon\Carbon;

class ContractStatsService
{
    use HasStatsFilters;

    /**
     * Build live contract stats for a date range, optionally scoped by branch and account.
     */
    public function stats(?int $branchId = null, ?int $accountId = null, ?Carbon $start = null, ?Carbon $end = null): array
    {
        return [
            'contracts'    => $this->contractsStats($branchId, $accountId, $start, $end),
            'installments' => $this->installmentsStats($branchId, $accountId, $start, $end),
            'draws'        => $this->drawsStats($branchId, $accountId, $start, $end),
        ];
    }

    /**
     * Build live contract stats for a whole month, optionally scoped by branch and account.
     */
    public function statsForMonth(Carbon $month, ?int $branchId = null, ?int $accountId = null): array
    {
        return $this->stats(
            $branchId,
            $accountId,
            $month->copy()->startOfMonth()->startOfDay(),
            $month->copy()->endOfMonth()->endOfDay(),
        );
    }

    /**
     * Compute and persist contract stats for a month at the finest grain:
     * one row per (month, branch_id, account_id).
     */
    public function computeAndStoreMonth(Carbon $month): void
    {
        $normalized = $month->copy()->startOfMonth();

        $pairs = Contract::query()
            ->select('branch_id', 'account_id')
            ->distinct()
            ->get();

        foreach ($pairs as $pair) {
            $this->storeMonth($normalized, $pair->branch_id, $pair->account_id);
        }
    }

    /**
     * Compute and upsert a single month/branch/account contract stats row.
     */
    private function storeMonth(Carbon $month, ?int $branchId, ?int $accountId): void
    {
        ContractStats::query()->updateOrCreate(
            [
                'month'      => $month->toDateString(),
                'branch_id'  => $branchId,
                'account_id' => $accountId,
            ],
            ['data' => $this->statsForMonth($month, $branchId, $accountId)],
        );
    }

    /**
     * New contracts, total amount, total cost and total profit.
     *
     * "total amount" maps to net_amount (the amount the client will pay),
     * "total cost" to the absolute purchase cost, and profit to their difference.
     */
    private function contractsStats(?int $branchId, ?int $accountId, ?Carbon $start, ?Carbon $end): array
    {
        $query = Contract::query()->byUserBranches();

        $this->applyBranch($query, 'branch_id', $branchId);
        $this->applyAccount($query, 'account_id', $accountId);
        $this->applyDateRange($query, $start, $end);

        $totals = (clone $query)->get([
            'net_amount',
            'purchase_cost',
        ]);

        $amount = (float) $totals->sum('net_amount');
        $cost   = (float) abs($totals->sum('purchase_cost'));

        return [
            'count'        => (clone $query)->count(),
            'total_amount' => round($amount, 2),
            'total_cost'   => round($cost, 2),
            'total_profit' => round($amount - $cost, 2),
        ];
    }

    /**
     * Installment counts per status and total paid/unpaid amounts.
     *
     * Partially-paid installments are counted separately, but their amounts are
     * split: the settled (paid) portion is folded into the paid total and the
     * remaining portion into the unpaid total.
     */
    private function installmentsStats(?int $branchId, ?int $accountId, ?Carbon $start, ?Carbon $end): array
    {
        $query = Installment::query()
            ->whereHas('contract', fn ($q) => $q->byUserBranches());

        if ($branchId !== null) {
            $query->whereHas('contract', fn ($q) => $q->where('branch_id', $branchId));
        }

        if ($accountId !== null) {
            $query->whereHas('contract', fn ($q) => $q->where('account_id', $accountId));
        }

        $this->applyDateRange($query, $start, $end, 'due_date');

        $paid      = (clone $query)->where('status', InstallmentStatus::PAID);
        $unpaid    = (clone $query)->where('status', InstallmentStatus::UNPAID);
        $partially = (clone $query)->where('status', InstallmentStatus::PARTIALLY_PAID);

        // Paid portion of partially-paid installments = sum of their settled draws.
        $partiallyPaidPortion = (float) $partially
            ->withSum(['draws as settled_amount' => fn ($q) => $q->whereIn(
                'status',
                [DrawStatus::PAID_ON_TIME->value, DrawStatus::LATE_PAYMENT->value],
            )], 'amount')
            ->get()
            ->sum('settled_amount');

        $paidTotal = (float) $paid->sum('amount') + $partiallyPaidPortion;

        $unpaidTotal = (float) $unpaid->sum('amount')
            + (float) $partially->sum('amount')
            - $partiallyPaidPortion;

        return [
            'count' => [
                'paid'           => $paid->count(),
                'unpaid'         => $unpaid->count(),
                'partially_paid' => $partially->count(),
            ],
            'total' => [
                'paid'   => round($paidTotal, 2),
                'unpaid' => round($unpaidTotal, 2),
            ],
        ];
    }

    /**
     * Draw counts and totals per status.
     *
     * Draws belong to a subscription (which belongs to a contract), so branch
     * scoping flows through the contract. Settled draws are those paid on time
     * or paid late; their amount counts as "paid", while postponed and failed
     * draws remain "unpaid".
     */
    private function drawsStats(?int $branchId, ?int $accountId, ?Carbon $start, ?Carbon $end): array
    {
        $query = Draw::query()
            ->whereHas('subscription.contract', fn ($q) => $q->byUserBranches());

        if ($branchId !== null) {
            $query->whereHas('subscription.contract', fn ($q) => $q->where('branch_id', $branchId));
        }

        if ($accountId !== null) {
            $query->whereHas('subscription.contract', fn ($q) => $q->where('account_id', $accountId));
        }

        $this->applyDateRange($query, $start, $end, 'due_date');

        $paidOnTime  = (clone $query)->where('status', DrawStatus::PAID_ON_TIME);
        $latePayment = (clone $query)->where('status', DrawStatus::LATE_PAYMENT);
        $postponed   = (clone $query)->where('status', DrawStatus::POSTPONED);
        $failed      = (clone $query)->where('status', DrawStatus::FAILED);

        $paidTotal = (float) $paidOnTime->sum('amount') + (float) $latePayment->sum('amount');

        $unpaidTotal = (float) $postponed->sum('amount') + (float) $failed->sum('amount');

        $taxTotal = (float) $query->sum('tax_amount');

        $taxedCount = (clone $query)->where('tax_amount', '>', 0)->count();

        return [
            'count' => [
                'paid_on_time'  => $paidOnTime->count(),
                'late_payment'  => $latePayment->count(),
                'postponed'     => $postponed->count(),
                'failed'        => $failed->count(),
                'taxed'         => $taxedCount,
            ],
            'total' => [
                'paid'   => round($paidTotal, 2),
                'unpaid' => round($unpaidTotal, 2),
                'tax'    => round($taxTotal, 2),
            ],
        ];
    }
}
