<?php

namespace Database\Seeders;

use App\Enums\ContractStatus;
use App\Enums\InstallmentStatus;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Installment;
use App\Models\Stock;
use App\Services\ContractService;
use Illuminate\Database\Seeder;

class ContractSeeder extends Seeder
{
    public function run(): void
    {
        $branch  = Branch::where('code', 'M')->first();
        $client  = Client::first();
        $account = Account::first();

        if (! $branch || ! $client || ! $account) {
            return;
        }

        if (Contract::where('branch_id', $branch->id)->exists()) {
            return;
        }

        // A stock in the branch inventory with the most quantity on hand, so the
        // configured contracts have enough stock to deduct from (deductStock
        // consumes batch quantity). We need at least 6 units for the 6 configured
        // contracts.
        $stock = Stock::whereHas('inventory', fn ($q) => $q->where('branch_id', $branch->id))
            ->withSum('batches as total_current', 'current_quantity')
            ->having('total_current', '>=', 6)
            ->orderByDesc('total_current')
            ->first();

        if (! $stock) {
            return;
        }

        $product = $stock->product;

        $service = app(ContractService::class);

        // Amounts: 1 item × 200,000 = 200,000 total.
        // Advance 20,000 → net 180,000 over 12 months → monthly 15,000.
        $baseItem = [[
            'product_id' => $product->id,
            'stock_id'   => $stock->id,
            'quantity'   => 1,
            'price'      => 200000,
        ]];

        $base = [
            'client_id'      => $client->id,
            'account_id'     => $account->id,
            'branch_id'      => $branch->id,
            'advance_amount' => 20000,
            'months_count'   => 12,
        ];

        // 1. PENDING
        $service->create($base + [
            'note'  => 'Example: pending',
            'items' => $baseItem,
        ]);

        // 2. APPROVED
        $approved = $service->create($base + [
            'note'  => 'Example: approved',
            'items' => $baseItem,
        ]);
        $service->approve($approved, 180000);

        // 3. REJECTED
        $rejected = $service->create($base + [
            'note'  => 'Example: rejected',
            'items' => $baseItem,
        ]);
        $service->reject($rejected);

        // 4. CONFIGURED
        $configured = $service->create($base + [
            'note'  => 'Example: configured',
            'items' => $baseItem,
        ]);
        $service->approve($configured, 180000);
        $service->configure($configured, 2, '2026-06-15');

        // 5. ACTIVE — configured then marked active (as if a draw lock was created).
        $active = $service->create($base + [
            'note'  => 'Example: active',
            'items' => $baseItem,
        ]);
        $service->approve($active, 180000);
        $service->configure($active, 2, '2026-06-15');
        $active->update(['status' => ContractStatus::ACTIVE]);
        // Draws have been attempted and failed, leaving installments unpaid.
        $active->installments()->update(['status' => InstallmentStatus::UNPAID]);

        // 6. COMPLETED — active with every installment paid.
        $completed = $service->create($base + [
            'note'  => 'Example: completed',
            'items' => $baseItem,
        ]);
        $service->approve($completed, 180000);
        $service->configure($completed, 2, '2026-01-15');
        $completed->update(['status' => ContractStatus::ACTIVE]);
        $completed->installments()->update(['status' => InstallmentStatus::PAID]);
        $completed->update(['status' => ContractStatus::COMPLETED]);

        // 7. CLOSED — active with installments not fully paid (past end date).
        $closed = $service->create($base + [
            'note'  => 'Example: closed',
            'items' => $baseItem,
        ]);
        $service->approve($closed, 180000);
        $service->configure($closed, 2, '2026-01-15');
        $closed->update(['status' => ContractStatus::ACTIVE]);
        $closed->installments()->update(['status' => InstallmentStatus::UNPAID]);
        $closed->update(['status' => ContractStatus::CLOSED]);

        // 8. UNPROCESSED — active with a PENDING installment whose due date is today.
        $unprocessed = $service->create($base + [
            'note'  => 'Example: unprocessed',
            'items' => $baseItem,
        ]);
        $service->approve($unprocessed, 180000);
        $service->configure($unprocessed, 2, '2026-06-15');
        $unprocessed->update(['status' => ContractStatus::ACTIVE]);
        // First installment is PENDING (default) with a due date on/before today.
        Installment::query()
            ->where('contract_id', $unprocessed->id)
            ->orderBy('due_date')
            ->first()
            ->update(['due_date' => now()->toDateString()]);

        // 9. DELINQUENT — active with exactly one PENDING installment and at
        // least one UNPAID/PARTIALLY_PAID installment.
        $delinquent = $service->create($base + [
            'note'  => 'Example: delinquent',
            'items' => $baseItem,
        ]);
        $service->approve($delinquent, 180000);
        $service->configure($delinquent, 2, '2026-06-15');
        $delinquent->update(['status' => ContractStatus::ACTIVE]);

        $installments = $delinquent->installments()->orderBy('due_date')->get();
        $installments->first()->update(['status' => InstallmentStatus::UNPAID]);
        $installments->get(1)->update(['status' => InstallmentStatus::PENDING]);
        $installments->slice(2)->each->update(['status' => InstallmentStatus::PAID]);
    }
}
