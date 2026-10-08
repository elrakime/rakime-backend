<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\ContractStatsService;
use App\Services\SaleStatsService;
use Illuminate\Database\Seeder;

class StatsSeeder extends Seeder
{
    public function run(): void
    {
        $saleStats = app(SaleStatsService::class);
        $contractStats = app(ContractStatsService::class);

        foreach (range(1, 3) as $monthsAgo) {
            $month = now()->subMonths($monthsAgo)->startOfMonth();

            $saleStats->computeAndStoreMonth($month);
            $contractStats->computeAndStoreMonth($month);
        }
    }
}
