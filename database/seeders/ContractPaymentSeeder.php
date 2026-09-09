<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\ContractPayment;
use App\Services\ContractPaymentService;
use Illuminate\Database\Seeder;

class ContractPaymentSeeder extends Seeder
{
    public function run(): void
    {
        // Pick a configured contract (has installments to settle).
        $contract = Contract::whereIn('status', ['configured', 'active'])
            ->whereHas('installments')
            ->first();

        if (! $contract) {
            return;
        }

        if (ContractPayment::where('contract_id', $contract->id)->exists()) {
            return;
        }

        $monthlyAmount = (float) $contract->monthly_amount;

        if ($monthlyAmount <= 0) {
            return;
        }

        // Pay exactly one installment's worth.
        app(ContractPaymentService::class)->create(
            $contract,
            $monthlyAmount,
            'Seeded contract payment',
        );
    }
}
