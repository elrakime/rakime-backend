<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\WalletMovementType;
use App\Models\Batch;
use App\Models\Branch;
use App\Models\Client;
use App\Models\InventoryMovement;
use App\Models\Wallet;
use App\Models\WalletMovement;
use App\Traits\HasStatsFilters;
use Carbon\Carbon;

class StatsService
{
    use HasStatsFilters;

    /**
     * Movement types reported when a date range is provided.
     */
    private const RANGE_MOVEMENT_TYPES = [
        WalletMovementType::DEPOSIT,
        WalletMovementType::WITHDRAWAL,
        WalletMovementType::SALARY,
        WalletMovementType::EXPENSE,
        WalletMovementType::ADJUSTMENT,
    ];

    /**
     * Build the live-only snapshot stats, optionally scoped by branch and a
     * creation date range.
     */
    public function snapshot(?int $branchId = null, ?Carbon $start = null, ?Carbon $end = null): array
    {
        return [
            'inventories' => $this->inventoriesStats($branchId, $start, $end),
            'wallets'     => $this->walletsStats($branchId, $start, $end),
            'clients'     => $this->clientsStats($branchId, $start, $end),
        ];
    }

    /**
     * Inventory stats.
     *
     * Without a date range: total cost (sum of quantity * purchase_price) and
     * total quantities across all batches.
     *
     * With a date range: total quantities as the sum of the `new_quantity` of
     * the last inventory movement per stock created in that period.
     */
    private function inventoriesStats(?int $branchId, ?Carbon $start, ?Carbon $end): array
    {
        if ($start !== null || $end !== null) {
            return [
                'total_quantities' => $this->inventoriesRangeQuantities($branchId, $start, $end),
            ];
        }

        $query = Batch::query()->byUserBranches();

        if ($branchId !== null) {
            $query->whereHas('stock.inventory', fn ($q) => $q->where('branch_id', $branchId));
        }

        $totalCost = (float) (clone $query)->get([
            'current_quantity',
            'purchase_price',
        ])->sum(fn ($batch) => $batch->current_quantity * $batch->purchase_price);

        return [
            'total_cost'       => round($totalCost, 2),
            'total_quantities' => (int) (clone $query)->sum('current_quantity'),
        ];
    }

    /**
     * Sum of the last inventory movement `new_quantity` per stock within the
     * date range.
     */
    private function inventoriesRangeQuantities(?int $branchId, ?Carbon $start, ?Carbon $end): int
    {
        $query = InventoryMovement::query()->byUserBranches();

        if ($branchId !== null) {
            $query->whereHas('inventory', fn ($q) => $q->where('branch_id', $branchId));
        }

        $this->applyDateRange($query, $start, $end);

        $movements = (clone $query)
            ->select('stock_id', 'new_quantity', 'created_at')
            ->orderBy('created_at')
            ->get();

        return (int) $movements
            ->groupBy('stock_id')
            ->sum(fn ($group) => (int) $group->last()->new_quantity);
    }

    /**
     * Wallet stats.
     *
     * Without a date range: total balance plus the per-movement-type totals.
     *
     * With a date range: total balance as the sum of the `new_balance` of the
     * last wallet movement per wallet within the period, plus the
     * per-movement-type totals for that period.
     */
    private function walletsStats(?int $branchId, ?Carbon $start, ?Carbon $end): array
    {
        $stats = [
            'total_balance' => round((float) $this->walletsTotalBalance($branchId, $start, $end), 2),
            'movements'     => $this->walletsMovementStats($branchId, $start, $end),
        ];

        return $stats;
    }

    /**
     * Total wallet balance. Without a date range this is the sum of the
     * wallets' current balance; with a date range it is the sum of the
     * `new_balance` of the last wallet movement per wallet in that period.
     */
    private function walletsTotalBalance(?int $branchId, ?Carbon $start, ?Carbon $end): float
    {
        if ($start === null && $end === null) {
            $query = Wallet::query()->byUserBranches();

            if ($branchId !== null) {
                $query->where('owner_type', Branch::class)
                    ->where('owner_id', $branchId);
            }

            return (float) $query->sum('balance');
        }

        $query = WalletMovement::query()->byUserBranches();

        if ($branchId !== null) {
            $query->whereHas('wallet', fn ($q) => $q->where('owner_type', Branch::class)->where('owner_id', $branchId));
        }

        $this->applyDateRange($query, $start, $end);

        $movements = (clone $query)
            ->select('wallet_id', 'new_balance', 'created_at')
            ->orderBy('created_at')
            ->get();

        return (float) $movements
            ->groupBy('wallet_id')
            ->sum(fn ($group) => (float) $group->last()->new_balance);
    }

    /**
     * Total amount per movement type.
     */
    private function walletsMovementStats(?int $branchId, ?Carbon $start, ?Carbon $end): array
    {
        $query = WalletMovement::query()->byUserBranches();

        if ($branchId !== null) {
            $query->whereHas('wallet', fn ($q) => $q->where('owner_type', Branch::class)->where('owner_id', $branchId));
        }

        $this->applyDateRange($query, $start, $end);

        $movements = [];

        foreach (self::RANGE_MOVEMENT_TYPES as $type) {
            $movements[$type->value] = round(
                (float) (clone $query)->where('movement_type', $type)->sum('amount'),
                2,
            );
        }

        return $movements;
    }

    /**
     * Clients stats.
     *
     * Without a date range: the total number of clients.
     *
     * With a date range: the total number of clients up to (before) the start
     * of the range, plus the number of new clients created in the range.
     */
    private function clientsStats(?int $branchId, ?Carbon $start, ?Carbon $end): array
    {
        $query = Client::query()->byUserBranches();

        $this->applyBranch($query, 'branch_id', $branchId);

        if ($start === null && $end === null) {
            return [
                'total' => (clone $query)->count(),
            ];
        }

        $beforeQuery = (clone $query);

        if ($start !== null) {
            $beforeQuery->where('created_at', '<', $start);
        }

        $newQuery = (clone $query);
        $this->applyDateRange($newQuery, $start, $end);

        return [
            'total' => $beforeQuery->count(),
            'new'   => $newQuery->count(),
        ];
    }
}
