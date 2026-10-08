<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ContractStatsService;
use App\Services\SaleStatsService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateMonthlyStats extends Command
{
    protected $signature = 'stats:generate-month
        {--month= : The month to generate stats for, formatted YYYY-MM. Defaults to last month.}
        {--type=all : Which stats to generate: sale, contract, or all.}';

    protected $description = 'Compute and store the monthly stats (sale and/or contract) into their tables.';

    public function handle(SaleStatsService $saleStats, ContractStatsService $contractStats): int
    {
        $month = $this->option('month')
            ? Carbon::parse($this->option('month'))
            : now()->subMonth();

        $month = $month->startOfMonth();

        $type = strtolower((string) $this->option('type'));

        if (! in_array($type, ['sale', 'contract', 'all'], true)) {
            $this->error('Invalid --type value. Allowed values: sale, contract, all.');

            return self::FAILURE;
        }

        if (in_array($type, ['sale', 'all'], true)) {
            $saleStats->computeAndStoreMonth($month);
            $this->info(sprintf('Sale stats generated for %s.', $month->format('Y-m')));
        }

        if (in_array($type, ['contract', 'all'], true)) {
            $contractStats->computeAndStoreMonth($month);
            $this->info(sprintf('Contract stats generated for %s.', $month->format('Y-m')));
        }

        return self::SUCCESS;
    }
}
