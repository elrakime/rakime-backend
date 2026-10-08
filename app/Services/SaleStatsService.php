<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Branch;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleStats;
use App\Traits\HasStatsFilters;
use Carbon\Carbon;

class SaleStatsService
{
    use HasStatsFilters;

    /**
     * Build live sale stats for a date range, optionally scoped by branch.
     */
    public function stats(?int $branchId = null, ?Carbon $start = null, ?Carbon $end = null): array
    {
        return [
            'sales'     => $this->salesStats($branchId, $start, $end),
            'purchases' => $this->purchasesStats($branchId, $start, $end),
        ];
    }

    /**
     * Build live sale stats for a whole month, optionally scoped by branch.
     */
    public function statsForMonth(Carbon $month, ?int $branchId = null): array
    {
        return $this->stats(
            $branchId,
            $month->copy()->startOfMonth()->startOfDay(),
            $month->copy()->endOfMonth()->endOfDay(),
        );
    }

    /**
     * Compute and persist sale stats for a month: one "total" row (branch_id = null)
     * plus one row per branch.
     */
    public function computeAndStoreMonth(Carbon $month): void
    {
        $normalized = $month->copy()->startOfMonth();

        $this->storeMonth($normalized, null);

        foreach (Branch::query()->orderBy('id')->pluck('id') as $branchId) {
            $this->storeMonth($normalized, $branchId);
        }
    }

    /**
     * Compute and upsert a single month/branch sale stats row.
     */
    private function storeMonth(Carbon $month, ?int $branchId): void
    {
        SaleStats::query()->updateOrCreate(
            ['month' => $month->toDateString(), 'branch_id' => $branchId],
            ['data' => $this->statsForMonth($month, $branchId)],
        );
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
            'count'        => (clone $query)->count(),
            'total_amount' => round($amount, 2),
            'total_cost'   => round($cost, 2),
            'total_profit' => round($amount - $cost, 2),
        ];
    }

    /**
     * Purchase counts per payment status and total paid/unpaid amounts.
     *
     * Payment status is derived (unpaid, partially_paid, paid) from paid_amount
     * versus net_amount, so it is computed inline via SQL conditions.
     */
    private function purchasesStats(?int $branchId, ?Carbon $start, ?Carbon $end): array
    {
        $query = Purchase::query()->byUserBranches();

        $this->applyBranch($query, 'branch_id', $branchId);
        $this->applyDateRange($query, $start, $end);

        $paid      = (clone $query)->whereRaw('paid_amount >= net_amount');
        $unpaid    = (clone $query)->whereRaw('paid_amount <= 0');
        $partially = (clone $query)->whereRaw('paid_amount > 0')->whereRaw('paid_amount < net_amount');

        $paidTotal   = (float) $paid->sum('paid_amount');
        $unpaidTotal = (float) $unpaid->sum('net_amount')
            + (float) $partially->sum('net_amount')
            - (float) $partially->sum('paid_amount');

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
}
