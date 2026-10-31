<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\FinancialRecordController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\WalletMovementController;
use App\Http\Controllers\Api\WalletTransferController;
use App\Http\Controllers\Api\WilayaController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login'])->middleware('guest');
        Route::middleware(['auth:sanctum', 'client.type:mobile', 'user.active', 'user.role'])->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
            Route::put('update-profile', [AuthController::class, 'updateProfile']);
            Route::post('change-password', [AuthController::class, 'changePassword']);
        });
    });

    Route::middleware(['auth:sanctum', 'client.type:mobile', 'user.active', 'user.role'])->group(function () {
        Route::post('device-tokens', [DeviceTokenController::class, 'register']);
        Route::delete('device-tokens', [DeviceTokenController::class, 'destroy']);

        Route::get('notifications', [NotificationController::class, 'index']);
        Route::post('notifications/{notification}/read', [NotificationController::class, 'read']);

        Route::get('clients/delinquent', [ClientController::class, 'delinquent']);
        Route::apiResource('clients', ClientController::class);

        Route::apiResource('wilayas', WilayaController::class)->only(['index']);

        Route::apiResource('contracts', ContractController::class)->only(['index', 'store', 'show']);
        Route::post('contracts/{contract}/approve', [ContractController::class, 'approve']);
        Route::post('contracts/{contract}/reject', [ContractController::class, 'reject']);
        Route::post('contracts/{contract}/configure', [ContractController::class, 'configure']);

        Route::apiResource('branches', BranchController::class)->only(['index']);

        Route::apiResource('stocks', StockController::class)->only(['index']);

        Route::apiResource('wallets', WalletController::class)->only(['index']);
        Route::post('wallets/{wallet}/deposit', [WalletController::class, 'deposit']);
        Route::post('wallets/{wallet}/withdraw', [WalletController::class, 'withdraw']);

        Route::apiResource('wallet-movements', WalletMovementController::class)->only(['index']);

        Route::apiResource('accounts', AccountController::class)->only(['index']);

        Route::get('financial-records', [FinancialRecordController::class, 'index']);
        Route::post('financial-records', [FinancialRecordController::class, 'store']);
        Route::put('financial-records/{financial_record}', [FinancialRecordController::class, 'update']);
        Route::delete('financial-records/{financial_record}', [FinancialRecordController::class, 'destroy']);

        Route::apiResource('wallet-transfers', WalletTransferController::class)->only(['index', 'store']);
    });
});