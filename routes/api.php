<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Web\AccountController;
use App\Http\Controllers\Web\AccountDrawLockController;
use App\Http\Controllers\Web\BatchController;
use App\Http\Controllers\Web\BranchController;
use App\Http\Controllers\Web\BrandController;
use App\Http\Controllers\Web\CategoryController;
use App\Http\Controllers\Web\CcpController;
use App\Http\Controllers\Web\ClientController;
use App\Http\Controllers\Web\ColorController;
use App\Http\Controllers\Web\ContractController;
use App\Http\Controllers\Web\DrawController;
use App\Http\Controllers\Web\ExpirationController;
use App\Http\Controllers\Web\FinancialRecordController;
use App\Http\Controllers\Web\InstallmentController;
use App\Http\Controllers\Web\InventoryController;
use App\Http\Controllers\Web\InventoryMovementController;
use App\Http\Controllers\Web\InventoryTransferController;
use App\Http\Controllers\Web\ContractPaymentController;
use App\Http\Controllers\Web\PermissionController;
use App\Http\Controllers\Web\PriceController;
use App\Http\Controllers\Web\ProductController;
use App\Http\Controllers\Web\PurchaseController;
use App\Http\Controllers\Web\PurchasePaymentController;
use App\Http\Controllers\Web\PurchaseReturnController;
use App\Http\Controllers\Web\RestockController;
use App\Http\Controllers\Web\RoleController;
use App\Http\Controllers\Web\SaleController;
use App\Http\Controllers\Web\SaleReturnController;
use App\Http\Controllers\Web\StockController;
use App\Http\Controllers\Web\SubscriptionController;
use App\Http\Controllers\Web\SupplierController;
use App\Http\Controllers\Web\TypeController;
use App\Http\Controllers\Web\UserController;
use App\Http\Controllers\Web\WalletController;
use App\Http\Controllers\Web\WalletMovementController;
use App\Http\Controllers\Web\WalletTransferController;
use App\Http\Controllers\Web\WilayaController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->middleware('guest');

    Route::middleware(['auth:sanctum', 'client.type:mobile', 'user.active'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::put('update-profile', [AuthController::class, 'updateProfile']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
    });
});

Route::prefix('v1')->middleware(['auth:sanctum', 'client.type:mobile', 'user.active'])->group(function () {
    Route::apiResource('users', UserController::class);
    Route::apiResource('roles', RoleController::class);
    Route::get('permissions', [PermissionController::class, 'index']);
    Route::apiResource('branches', BranchController::class);
    Route::apiResource('accounts', AccountController::class);
    Route::get('accounts/{account}/export-registrations', [AccountController::class, 'exportRegistrations']);
    Route::get('accounts/{account}/export-cancellations', [AccountController::class, 'exportCancellations']);
    Route::post('accounts/{account}/import', [AccountController::class, 'import']);
    Route::apiResource('account-draw-locks', AccountDrawLockController::class)->only(['index', 'store']);
    Route::apiResource('wilayas', WilayaController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('brands', BrandController::class);
    Route::apiResource('types', TypeController::class);
    Route::apiResource('colors', ColorController::class);
    Route::apiResource('clients', ClientController::class);
    Route::get('clients/find/{keyword}', [ClientController::class, 'find']);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('inventories', InventoryController::class);
    Route::get('inventory-movements', [InventoryMovementController::class, 'index']);
    Route::apiResource('wallets', WalletController::class);
    Route::post('wallets/{wallet}/deposit', [WalletController::class, 'deposit']);
    Route::post('wallets/{wallet}/withdraw', [WalletController::class, 'withdraw']);
    Route::apiResource('wallet-movements', WalletMovementController::class)->only(['index']);
    Route::apiResource('wallet-transfers', WalletTransferController::class)->only(['index', 'store', 'show', 'destroy']);
    Route::apiResource('suppliers', SupplierController::class);
    Route::apiResource('inventory-transfers', InventoryTransferController::class);
    Route::post('inventory-transfers/{inventory_transfer}/dispatch', [InventoryTransferController::class, 'dispatch']);
    Route::post('inventory-transfers/{inventory_transfer}/receive', [InventoryTransferController::class, 'receive']);
    Route::post('inventory-transfers/{inventory_transfer}/cancel', [InventoryTransferController::class, 'cancel']);
    Route::apiResource('purchases', PurchaseController::class);
    Route::post('purchases/{purchase}/receive', [PurchaseController::class, 'receive']);
    Route::post('purchases/{purchase}/cancel', [PurchaseController::class, 'cancel']);
    Route::apiResource('stocks', StockController::class)->except(['update']);
    Route::apiResource('purchases.payments', PurchasePaymentController::class)->parameters(['payments' => 'purchase_payment'])->only(['index', 'store']);
    Route::post('purchases/{purchase}/payments/{purchase_payment}/cancel', [PurchasePaymentController::class, 'cancel']);
    Route::apiResource('purchase-returns', PurchaseReturnController::class)->parameters(['purchase-returns' => 'purchase_return']);
    Route::post('purchase-returns/{purchase_return}/approve', [PurchaseReturnController::class, 'approve']);
    Route::post('purchase-returns/{purchase_return}/cancel', [PurchaseReturnController::class, 'cancel']);

    Route::apiResource('restocks', RestockController::class);
    Route::post('restocks/{restock}/submit', [RestockController::class, 'submit']);
    Route::post('restocks/{restock}/cancel', [RestockController::class, 'cancel']);
    Route::post('restocks/{restock}/fulfill', [RestockController::class, 'fulfill']);

    Route::apiResource('sales', SaleController::class);
    Route::apiResource('sale-returns', SaleReturnController::class)->parameters(['sale-returns' => 'sale_return']);
    Route::post('sale-returns/{sale_return}/approve', [SaleReturnController::class, 'approve']);
    Route::post('sale-returns/{sale_return}/cancel', [SaleReturnController::class, 'cancel']);

    Route::apiResource('stocks.prices', PriceController::class);
    Route::apiResource('stocks.batches', BatchController::class);
    Route::apiResource('expirations', ExpirationController::class);
    Route::post('expirations/{expiration}/approve', [ExpirationController::class, 'approve']);

    Route::apiResource('contracts', ContractController::class)->only(['index', 'store', 'show', 'update']);
    Route::post('contracts/{contract}/approve', [ContractController::class, 'approve']);
    Route::post('contracts/{contract}/reject', [ContractController::class, 'reject']);
    Route::post('contracts/{contract}/payments', [ContractPaymentController::class, 'store']);
    Route::post('contracts/{contract}/configure', [ContractController::class, 'configure']);
    Route::post('contracts/{contract}/cancel', [ContractController::class, 'cancel']);

    Route::apiResource('contracts.installments', InstallmentController::class)->only(['index']);
    Route::apiResource('contracts.subscriptions', SubscriptionController::class)->only(['index']);
    Route::apiResource('contracts.draws', DrawController::class)->only(['index']);

    Route::get('financial-records', [FinancialRecordController::class, 'index']);
    Route::post('financial-records', [FinancialRecordController::class, 'store']);
    Route::put('financial-records/{financial_record}', [FinancialRecordController::class, 'update']);
    Route::delete('financial-records/{financial_record}', [FinancialRecordController::class, 'destroy']);

});
