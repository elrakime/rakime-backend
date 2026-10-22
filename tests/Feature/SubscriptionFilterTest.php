<?php

declare(strict_types=1);

use App\Enums\ContractStatus;
use App\Enums\InstallmentPaymentMethod;
use App\Enums\InstallmentStatus;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Installment;
use App\Models\Subscription;
use App\Models\Wilaya;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

function makeFilterableContract(): Contract
{
    static $sequence = 0;
    $sequence++;

    $wilaya = Wilaya::create(['name' => "Wilaya {$sequence}"]);

    $branch = Branch::create([
        'wilaya_id' => $wilaya->id,
        'name' => "Branch {$sequence}",
        'code' => "B{$sequence}",
    ]);

    $client = Client::create([
        'branch_id' => $branch->id,
        'wilaya_id' => $wilaya->id,
        'firstname' => 'John',
        'lastname' => 'Doe',
        'phone' => '0550'.$sequence,
        'salary' => 50000,
        'nin' => 'NIN'.$sequence,
        'ccp_number' => 'CCP'.$sequence,
        'ccp_key' => 'key',
    ]);

    $account = Account::create([
        'name' => "Account {$sequence}",
        'ccp_number' => 'ACC'.$sequence,
        'ccp_key' => 'key',
        'draw_day' => 1,
        'min_withdraw_amount' => 100,
        'max_withdraw_count' => 5,
    ]);

    return Contract::create([
        'client_id' => $client->id,
        'account_id' => $account->id,
        'branch_id' => $branch->id,
        'status' => ContractStatus::ACTIVE,
        'net_amount' => 10000,
        'months_count' => 10,
        'monthly_amount' => 1000,
    ]);
}

function makeInstallment(Contract $contract, InstallmentStatus $status, string $dueDate): Installment
{
    return Installment::create([
        'contract_id' => $contract->id,
        'amount' => 1000,
        'status' => $status,
        'payment_method' => InstallmentPaymentMethod::BANK->value,
        'due_date' => $dueDate,
    ]);
}

function makeSubscription(Contract $contract): Subscription
{
    return Subscription::create([
        'contract_id' => $contract->id,
        'subscription_number' => 1,
        'amount' => 1000,
    ]);
}

function listSubscriptionIds(array $filter = []): array
{
    $request = Request::create('/api/subscriptions', 'GET', $filter === [] ? [] : ['filter' => $filter]);

    return app(SubscriptionService::class)
        ->list($request)
        ->pluck('id')
        ->sort()
        ->values()
        ->all();
}

test('installments are present in the response without any filter', function () {
    $contract = makeFilterableContract();
    makeInstallment($contract, InstallmentStatus::UNPAID, '2026-01-01');
    makeInstallment($contract, InstallmentStatus::PAID, '2026-02-01');
    makeSubscription($contract);

    $request = Request::create('/api/subscriptions', 'GET');

    $subscription = app(SubscriptionService::class)->list($request)->first();

    expect($subscription->contract->relationLoaded('installments'))->toBeTrue()
        ->and($subscription->contract->installments->pluck('due_date')->map->toDateString()->all())
        ->toBe(['2026-01-01', '2026-02-01'])
        ->and($subscription->contract->payment_status)->toBe(InstallmentStatus::PARTIALLY_PAID->value);
});

test('all_paid only returns subscriptions whose installments are all paid', function () {
    $fullyPaid = makeFilterableContract();
    makeInstallment($fullyPaid, InstallmentStatus::PAID, '2026-01-01');
    makeInstallment($fullyPaid, InstallmentStatus::PAID, '2026-02-01');
    $fullyPaidSubscription = makeSubscription($fullyPaid);

    $partiallyPaid = makeFilterableContract();
    makeInstallment($partiallyPaid, InstallmentStatus::PAID, '2026-01-01');
    makeInstallment($partiallyPaid, InstallmentStatus::UNPAID, '2026-02-01');
    makeSubscription($partiallyPaid);

    $unpaid = makeFilterableContract();
    makeInstallment($unpaid, InstallmentStatus::UNPAID, '2026-01-01');
    makeSubscription($unpaid);

    expect(listSubscriptionIds(['installments_status' => 'all_paid']))
        ->toBe([$fullyPaidSubscription->id]);
});

test('has_unpaid returns subscriptions with at least one unpaid installment', function () {
    $fullyPaid = makeFilterableContract();
    makeInstallment($fullyPaid, InstallmentStatus::PAID, '2026-01-01');
    makeSubscription($fullyPaid);

    $partiallyPaid = makeFilterableContract();
    makeInstallment($partiallyPaid, InstallmentStatus::PAID, '2026-01-01');
    makeInstallment($partiallyPaid, InstallmentStatus::PARTIALLY_PAID, '2026-02-01');
    $partiallyPaidSubscription = makeSubscription($partiallyPaid);

    $pending = makeFilterableContract();
    makeInstallment($pending, InstallmentStatus::PENDING, '2026-01-01');
    $pendingSubscription = makeSubscription($pending);

    expect(listSubscriptionIds(['installments_status' => 'has_unpaid']))
        ->toBe([$partiallyPaidSubscription->id, $pendingSubscription->id]);
});

test('contracts without installments fall in the has_unpaid bucket only', function () {
    $withoutInstallments = makeFilterableContract();
    $subscription = makeSubscription($withoutInstallments);

    $fullyPaid = makeFilterableContract();
    makeInstallment($fullyPaid, InstallmentStatus::PAID, '2026-01-01');
    $fullyPaidSubscription = makeSubscription($fullyPaid);

    expect(listSubscriptionIds(['installments_status' => 'all_paid']))->toBe([$fullyPaidSubscription->id])
        ->and(listSubscriptionIds(['installments_status' => 'has_unpaid']))->toBe([$subscription->id]);
});
