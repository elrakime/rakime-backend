<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ContractService;
use Illuminate\Console\Command;

class SyncContractStatuses extends Command
{
    protected $signature = 'contracts:sync-status';

    protected $description = 'Close or complete active contracts whose effective end date has passed.';

    public function handle(ContractService $contractService): int
    {
        $result = $contractService->syncExpiredStatuses();

        $this->info(sprintf(
            'Contracts synced: %d completed, %d closed.',
            $result['completed'],
            $result['closed'],
        ));

        return self::SUCCESS;
    }
}
