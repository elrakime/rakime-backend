<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\StatsService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class StatsSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(StatsService::class);

        foreach (range(1, 3) as $monthsAgo) {
            $service->computeAndStoreMonth(now()->subMonths($monthsAgo)->startOfMonth());
        }
    }
}
