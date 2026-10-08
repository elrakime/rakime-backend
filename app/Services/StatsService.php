<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Batch;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Wallet;
use App\Traits\HasStatsFilters;

class StatsService
{
    use HasStatsFilters;

    /**
     * Build the live-only snapshot stats, optionally scoped by branch.
     */
    public function snapshot(?int $branchId = null): array
    {
        return [
            'inventories' => $this->inventoriesStats($branchId),
            'wallets'     => $this->walletsStats($branchId),
            'clients'     => $this->clientsStats($branchId),
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
            $query->where('owner_type', Branch::class)
                ->where('owner_id', $branchId);
        }

        return [
            'total_balance' => round((float) $query->sum('balance'), 2),
        ];
    }

    /**
     * New clients count.
     */
    private function clientsStats(?int $branchId): array
    {
        $query = Client::query()->byUserBranches();

        $this->applyBranch($query, 'branch_id', $branchId);

        return [
            'count' => $query->count(),
        ];
    }
}
