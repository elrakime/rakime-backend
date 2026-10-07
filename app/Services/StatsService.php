<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DrawStatus;
use App\Enums\InstallmentStatus;
use App\Models\Batch;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Draw;
use App\Models\Installment;
use App\Models\Sale;
use App\Models\Stats;
use App\Models\Wallet;
use Carbon\Carbon;

class StatsService
{
    /**
     * Build the dashboard stats, optionally scoped by branch and date range.
     *
     * @param  int|null  $branchId
     * @param  string|null  $fromDate
     * @param  string|null  $toDate
     */
    public function stats(?int $branchId = null, ?string $fromDate = null, ?string $toDate = null): array
    {
        $start = $fromDate ? Carbon::parse($fromDate)->startOfDay() : null;
        $end   = $toDate ? Carbon::parse($toDate)->endOfDay() : null;

        return $this->buildStats($branchId, $start, $end);
    }

    /**
     * Build the dashboard stats for a whole month, optionally scoped by branch.
     */
    public function statsForMonth(Carbon $month, ?int $branchId = null): array
    {
        $start = $month->copy()->startOfMonth()->startOfDay();
        $end   = $month->copy()->endOfMonth()->endOfDay();

        return $this->buildStats($branchId, $start, $end);
    }

    /**
     * Compute the stats for a given month and persist them into the stats table:
     * one "total" row (branch_id = null) plus one row per branch.
     *
     * Upserts on (month, branch_id) so re-running is idempotent.
     */
    public function computeAndStoreMonth(Carbon $month): void
    {
        $normalized = $month->copy()->startOfMonth();

        // Total across all branches.
        $this->storeMonth($normalized, null);

        foreach (Branch::query()->orderBy('id')->pluck('id') as $branchId) {
            $this->storeMonth($normalized, $branchId);
        }
    }

    /**
     * Compute and upsert a single month/branch stats row.
     */
    private function storeMonth(Carbon $month, ?int $branchId): void
    {
        Stats::query()->updateOrCreate(
            ['month' => $month->toDateString(), 'branch_id' => $branchId],
            ['data' => $this->statsForMonth($month, $branchId)],
        );
    }

    /**
     * Shared stats builder used by both the date-range and month variants.
     */
    private function buildStats(?int $branchId, ?Carbon $start, ?Carbon $end): array
    {
        return [
            'sales'        => $this->salesStats($branchId, $start, $end),
            'contracts'    => $this->contractsStats($branchId, $start, $end),
            'installments' => $this->installmentsStats($branchId, $start, $end),
            'draws'        => $this->drawsStats($branchId, $start, $end),
            'inventories'  => $this->inventoriesStats($branchId),
            'wallets'      => $this->walletsStats($branchId),
            'clients'      => $this->clientsStats($branchId, $start, $end),
        ];
    }

    /**
     * New sales, total amount, total cost and total profit.
     */
    private function salesStats(?int $branchId, ?Carbon $start, ?Carbon $end): array
    {
        $query = Sale::query()->byUserBranches();

        $this->applyBranch($query, 'branch_id', $branchId);
        $this->applyDateRange($query, $start, $end);

        $totals = (clone $query)->get([
            'total_amount',
            'purchase_cost',
        ]);

        $amount = (float) $totals->sum('total_amount');
        $cost   = (float) $totals->sum('purchase_cost');

        return [
            'count'              => (clone $query)->count(),
            'total_amount'       => round($amount, 2),
            'total_cost'         => round($cost, 2),
            'total_profit'       => round($amount - $cost, 2),
            'total_purchase_cost' => round($cost, 2),
            'net_profit'         => round($amount - $cost, 2),
        ];
    }

    /**
     * New contracts, total amount, total cost and total profit.
     *
     * "total amount" maps to net_amount (the amount the client will pay),
     * "total cost" to the absolute purchase cost, and profit to their difference.
     */
    private function contractsStats(?int $branchId, ?Carbon $start, ?Carbon $end): array
    {
        $query = Contract::query()->byUserBranches();

        $this->applyBranch($query, 'branch_id', $branchId);
        $this->applyDateRange($query, $start, $end);

        $totals = (clone $query)->get([
            'net_amount',
            'purchase_cost',
        ]);

        $amount = (float) $totals->sum('net_amount');
        $cost   = (float) abs($totals->sum('purchase_cost'));

        return [
            'count'              => (clone $query)->count(),
            'total_amount'       => round($amount, 2),
            'total_cost'         => round($cost, 2),
            'total_profit'       => round($amount - $cost, 2),
            'total_purchase_cost' => round($cost, 2),
            'net_profit'         => round($amount - $cost, 2),
        ];
    }

    /**
     * Installment counts per status and total paid/unpaid amounts.
     *
     * Partially-paid installments are counted separately, but their amounts are
     * split: the settled (paid) portion is folded into the paid total and the
     * remaining portion into the unpaid total.
     */
    private function installmentsStats(?int $branchId, ?Carbon $start, ?Carbon $end): array
    {
        $query = Installment::query()
            ->whereHas('contract', fn ($q) => $q->byUserBranches());

        if ($branchId !== null) {
            $query->whereHas('contract', fn ($q) => $q->where('branch_id', $branchId));
        }

        $this->applyDateRange($query, $start, $end);

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
    private function drawsStats(?int $branchId, ?Carbon $start, ?Carbon $end): array
    {
        $query = Draw::query()
            ->whereHas('subscription.contract', fn ($q) => $q->byUserBranches());

        if ($branchId !== null) {
            $query->whereHas('subscription.contract', fn ($q) => $q->where('branch_id', $branchId));
        }

        $this->applyDateRange($query, $start, $end);

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

    /**
     * Total batches cost (sum of quantity * purchase_price across all batches).
     */
    private function inventoriesStats(?int $branchId): array
    {
        $query = Batch::query()->byUserBranches();

        if ($branchId !== null) {
            $query->whereHas('stock.inventory', fn ($q) => $q->where('branch_id', $branchId));
        }

        $totalCost = (float) (clone $query)->get([
            'current_quantity',
            'purchase_price',
        ])->sum(fn ($batch) => $batch->current_quantity * $batch->purchase_price);

        return [
            'total_cost' => round($totalCost, 2),
        ];
    }

    /**
     * Total wallet balance.
     */
    private function walletsStats(?int $branchId): array
    {
        $query = Wallet::query()->byUserBranches();

        if ($branchId !== null) {
            $query->where('owner_type', \App\Models\Branch::class)
                ->where('owner_id', $branchId);
        }

        return [
            'total_balance' => round((float) $query->sum('balance'), 2),
        ];
    }

    /**
     * New clients count.
     */
    private function clientsStats(?int $branchId, ?Carbon $start, ?Carbon $end): array
    {
        $query = Client::query()->byUserBranches();

        $this->applyBranch($query, 'branch_id', $branchId);
        $this->applyDateRange($query, $start, $end);

        return [
            'count' => $query->count(),
        ];
    }

    /**
     * Apply an optional branch filter to a query.
     */
    private function applyBranch($query, string $column, ?int $branchId): void
    {
        if ($branchId !== null) {
            $query->where($column, $branchId);
        }
    }

    /**
     * Apply an optional created_at date range to a query.
     */
    private function applyDateRange($query, ?Carbon $start, ?Carbon $end): void
    {
        if ($start !== null) {
            $query->where('created_at', '>=', $start);
        }

        if ($end !== null) {
            $query->where('created_at', '<=', $end);
        }
    }
}
