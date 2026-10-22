<?php

declare(strict_types=1);

use App\Enums\WalletMovementType;
use App\Http\Requests\Web\Wallet\WithdrawWalletRequest;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

test('zakat is accepted by the withdraw request and recorded as a debit movement', function () {
    $validator = Validator::make(
        ['type' => WalletMovementType::ZAKAT->value, 'amount' => 25, 'note' => 'Annual zakat'],
        (new WithdrawWalletRequest)->rules(),
    );

    expect($validator->passes())->toBeTrue();

    $wallet = Wallet::create(['name' => 'Test wallet', 'balance' => 100]);

    $movement = app(WalletService::class)->zakat($wallet, 25, 'Annual zakat');

    expect($movement->movement_type)->toBe(WalletMovementType::ZAKAT);
    expect((float) $movement->amount)->toBe(-25.0);
    expect((float) $wallet->fresh()->balance)->toBe(75.0);
});
