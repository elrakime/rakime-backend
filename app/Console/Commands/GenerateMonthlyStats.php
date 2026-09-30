<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\StatsService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateMonthlyStats extends Command
{
    protected $signature = 'stats:generate-month {--month= : The month to generate stats for, formatted YYYY-MM. Defaults to last month.}';

    protected $description = 'Compute and store the monthly stats (total and per branch) into the stats table.';

    public function handle(StatsService $statsService): int
    {
        $month = $this->option('month')
            ? Carbon::parse($this->option('month'))
            : now()->subMonth();

        $month = $month->startOfMonth();

        $statsService->computeAndStoreMonth($month);

        $this->info(sprintf('Stats generated for %s.', $month->format('Y-m')));

        return self::SUCCESS;
    }
}
