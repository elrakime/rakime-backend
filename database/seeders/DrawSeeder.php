<?php

namespace Database\Seeders;

use App\Enums\DrawStatus;
use App\Models\Contract;
use App\Models\Draw;
use App\Services\AccountImportService;
use Illuminate\Database\Seeder;

class DrawSeeder extends Seeder
{
    public function run(): void
    {
        // Find a contract with subscriptions and installments.
        $contract = Contract::whereHas('subscriptions')
            ->whereHas('installments')
            ->first();

        if (! $contract) {
            return;
        }

        $subscriptions = $contract->subscriptions()->orderBy('id')->get();

        if ($subscriptions->count() < 2) {
            return;
        }

        // Skip if draws already exist for this contract's subscriptions.
        if (Draw::whereIn('subscription_id', $subscriptions->pluck('id'))->exists()) {
            return;
        }

        // Draw plan: [cycle (installment due_date), status, last_attempted date]
        // June: both subscriptions paid on time.
        // July: each subscription postponed then paid late (attempted in August).
        // August: both subscriptions failed.
        $plan = [
            ['2026-06-15', DrawStatus::PAID_ON_TIME, '15/06/2026'],
            ['2026-06-15', DrawStatus::PAID_ON_TIME, '15/06/2026'],
            ['2026-07-15', DrawStatus::POSTPONED,    '15/08/2026'],
            ['2026-07-15', DrawStatus::POSTPONED,    '15/08/2026'],
            ['2026-07-15', DrawStatus::LATE_PAYMENT, '15/08/2026'],
            ['2026-07-15', DrawStatus::LATE_PAYMENT, '15/08/2026'],
            ['2026-08-15', DrawStatus::FAILED,       '15/08/2026'],
            ['2026-08-15', DrawStatus::FAILED,       '15/08/2026'],
        ];

        $items = [];

        foreach ($plan as $index => [$cycle, $status, $date]) {
            // Round-robin the two subscriptions across the plan.
            $subscription = $subscriptions[$index % 2];

            $amount = (float) $subscription->amount;

            // Postponed/failed draws are taxed at 5% of the draw amount.
            $tax = in_array($status, [DrawStatus::POSTPONED, DrawStatus::FAILED], true)
                ? number_format($amount * 0.05, 2, '.', '')
                : '0.00';

            $items[] = [
                'subscription_reference' => $subscription->reference,
                'cycle'                  => $cycle,
                'status'                 => $status->value,
                'date'                   => $date,
                'amount'                 => (string) $amount,
                'tax'                    => $tax,
            ];
        }

        // Process the items through the import service, which creates the
        // draws, records wallet movements, and recomputes installment statuses.
        app(AccountImportService::class)->process($items);
    }
}
