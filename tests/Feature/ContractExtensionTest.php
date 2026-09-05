<?php

declare(strict_types=1);

use App\Enums\ContractStatus;
use App\Enums\DrawStatus;
use App\Enums\InstallmentStatus;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractPayment;
use App\Models\Draw;
use App\Models\Installment;
use App\Models\Subscription;
use App\Models\Wilaya;
use App\Services\ContractService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeContract(array $attributes = []): Contract
{
    $wilaya = Wilaya::create(['name' => 'Alger']);

    $branch = Branch::create([
        'wilaya_id' => $wilaya->id,
        'name'      => 'Branch',
        'code'      => 'B',
    ]);

    $client = Client::create([
        'branch_id'   => $branch->id,
        'wilaya_id'   => $wilaya->id,
        'firstname'   => 'John',
        'lastname'    => 'Doe',
        'phone'       => '0550' . rand(100000, 999999),
        'salary'      => 50000,
        'nin'         => 'NIN' . rand(100000, 999999),
        'ccp_number'  => 'CCP' . rand(100000, 999999),
        'ccp_key'     => 'key',
    ]);

    $account = Account::create([
        'name'                => 'Account',
        'ccp_number'          => 'ACC' . rand(100000, 999999),
        'ccp_key'             => 'key',
        'draw_day'            => 1,
        'min_withdraw_amount' => 100,
        'max_withdraw_count'  => 5,
    ]);

    return Contract::create(array_merge([
        'client_id'      => $client->id,
        'account_id'     => $account->id,
        'branch_id'      => $branch->id,
        'status'         => ContractStatus::CLOSED,
        'net_amount'     => 10000,
        'months_count'   => 10,
        'monthly_amount' => 1000,
    ], $attributes));
}

test('extending a closed contract creates a linked pending contract with remaining amount', function () {
    $contract = makeContract();

    // Simulate 4000 already paid: 2 settled draws of 1000 + 2 cash payments of 1000.
    $subscription = Subscription::create([
        'contract_id'         => $contract->id,
        'subscription_number' => 1,
        'amount'              => 1000,
    ]);

    $installment = Installment::create([
        'contract_id'    => $contract->id,
        'amount'         => 1000,
        'status'         => InstallmentStatus::PAID,
        'payment_method' => 'bank',
        'due_date'       => now()->subMonth()->toDateString(),
    ]);

    Draw::create([
        'subscription_id' => $subscription->id,
        'installment_id'  => $installment->id,
        'amount'          => 2000,
        'status'          => DrawStatus::PAID_ON_TIME,
        'due_date'        => now()->subMonth()->toDateString(),
    ]);

    ContractPayment::create([
        'contract_id' => $contract->id,
        'amount'      => 2000,
    ]);

    $extension = app(ContractService::class)->extend($contract, 6);

    expect($extension->parent_contract_id)->toBe($contract->id);
    expect($extension->status)->toBe(ContractStatus::PENDING);
    expect((float) $extension->net_amount)->toBe(6000.0);
    expect((float) $extension->monthly_amount)->toBe(1000.0); // ceil(6000 / 6)
    expect($extension->items()->count())->toBe(0);
    expect($contract->fresh()->status)->toBe(ContractStatus::CLOSED);
});

test('extending a non-closed/cancelled contract throws', function () {
    $contract = makeContract(['status' => ContractStatus::ACTIVE]);

    app(ContractService::class)->extend($contract, 6);
})->throws(Exception::class);

test('extending a contract with no remaining amount throws', function () {
    $contract = makeContract(['net_amount' => 0]);

    app(ContractService::class)->extend($contract, 6);
})->throws(Exception::class);

test('list hides superseded contracts by default', function () {
    $contract = makeContract();

    app(ContractService::class)->extend($contract, 6);

    $result = app(ContractService::class)->list(request()->merge([]));

    $ids = collect($result->items())->pluck('id');

    expect($ids)->not->toContain($contract->id);
});

test('list includes superseded contracts when include_superseded is set', function () {
    $contract = makeContract();

    app(ContractService::class)->extend($contract, 6);

    $request = request()->merge(['include_superseded' => 1]);

    $result = app(ContractService::class)->list($request);

    $ids = collect($result->items())->pluck('id');

    expect($ids)->toContain($contract->id);
});
