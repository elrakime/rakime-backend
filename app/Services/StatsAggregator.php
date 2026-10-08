<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ContractStats;
use App\Models\SaleStats;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StatsAggregator
{
    /**
     * Sum sale stats rows across a month range, optionally scoped by branch.
     *
     * Returns a "total" plus a per-month "months" series.
     */
    public function aggregateSales(?int $branchId, ?Carbon $fromMonth, ?Carbon $toMonth): array
    {
        $query = SaleStats::query();

        if ($branchId !== null) {
            $query->where('branch_id', $branchId);
        } else {
            $query->whereNull('branch_id');
        }

        $this->applyMonthRange($query, $fromMonth, $toMonth);

        $rows = $query->orderBy('month')->get();

        return [
            'total'  => $this->sumRows($rows),
            'months' => $this->monthSeries($rows),
        ];
    }

    /**
     * Sum contract stats rows across a month range, optionally scoped by branch and account.
     *
     * Returns a "total" plus a per-month "months" series.
     */
    public function aggregateContracts(?int $branchId, ?int $accountId, ?Carbon $fromMonth, ?Carbon $toMonth): array
    {
        $query = ContractStats::query();

        // Contract stats are stored at the finest grain (branch x account) with no
        // "total" row, so omitting the branch filter sums across all branches.
        if ($branchId !== null) {
            $query->where('branch_id', $branchId);
        }

        if ($accountId !== null) {
            $query->where('account_id', $accountId);
        }

        $this->applyMonthRange($query, $fromMonth, $toMonth);

        $rows = $query->orderBy('month')->get();

        return [
            'total'  => $this->sumRows($rows),
            'months' => $this->monthSeries($rows),
        ];
    }

    /**
     * Apply an optional month range to a query.
     */
    private function applyMonthRange($query, ?Carbon $fromMonth, ?Carbon $toMonth): void
    {
        if ($fromMonth !== null) {
            $query->where('month', '>=', $fromMonth->copy()->startOfMonth());
        }

        if ($toMonth !== null) {
            $query->where('month', '<=', $toMonth->copy()->startOfMonth());
        }
    }

    /**
     * Sum the "data" payload of a collection of stored rows.
     */
    private function sumRows(Collection $rows): array
    {
        $total = [];

        foreach ($rows as $row) {
            $total = $this->add($total, $row->data ?? []);
        }

        return $total;
    }

    /**
     * Build a per-month series from stored rows.
     */
    private function monthSeries(Collection $rows): array
    {
        return $rows->map(fn ($row) => [
            'month' => Carbon::parse($row->month)->format('Y-m'),
            'data'  => $row->data ?? [],
        ])->values()->all();
    }

    /**
     * Recursively sum two stat payloads (numbers are summed, counts summed, scalars kept).
     */
    private function add(array $a, array $b): array
    {
        foreach ($b as $key => $value) {
            if (! array_key_exists($key, $a)) {
                $a[$key] = $value;
                continue;
            }

            if (is_array($value) && is_array($a[$key])) {
                $a[$key] = $this->add($a[$key], $value);
            } elseif (is_numeric($value) && is_numeric($a[$key])) {
                $a[$key] = round((float) $a[$key] + (float) $value, 2);
            }
        }

        return $a;
    }
}
