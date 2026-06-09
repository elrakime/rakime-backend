<?php

use App\Http\Controllers\Api\BatchController;
use App\Http\Controllers\Api\PriceController;
use App\Http\Controllers\Web\AccountController;
use App\Http\Controllers\Web\Auth\AuthController;
use App\Http\Controllers\Web\BranchController;
use App\Http\Controllers\Web\BrandController;
use App\Http\Controllers\Web\CategoryController;
use App\Http\Controllers\Web\ClientController;
use App\Http\Controllers\Web\ColorController;
use App\Http\Controllers\Web\ExpirationController;
use App\Http\Controllers\Web\InventoryController;
use App\Http\Controllers\Web\InventoryMovementController;
use App\Http\Controllers\Web\PermissionController;
use App\Http\Controllers\Web\ProductController;
use App\Http\Controllers\Web\PurchaseController;
use App\Http\Controllers\Web\PurchaseReturnController;
use App\Http\Controllers\Web\RestockController;
use App\Http\Controllers\Web\RoleController;
use App\Http\Controllers\Web\StockController;
use App\Http\Controllers\Web\SupplierController;
use App\Http\Controllers\Web\TransferController;
use App\Http\Controllers\Web\TreasuryController;
use App\Http\Controllers\Web\TypeController;
use App\Http\Controllers\Web\UserController;
use App\Http\Controllers\Web\WilayaController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/docs', function () {
    return response()->file(resource_path('views/docs.html'));
});

Route::prefix('v1/auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->middleware('guest');

    Route::middleware(['auth:sanctum', 'client.type:web'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::put('update-profile', [AuthController::class, 'updateProfile']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
    });
});

Route::prefix('v1')->middleware(['auth:sanctum', 'client.type:web'])->group(function () {
    Route::apiResource('users', UserController::class);
    Route::apiResource('roles', RoleController::class);
    Route::get('permissions', [PermissionController::class, 'index']);
    Route::apiResource('branches', BranchController::class);
    Route::apiResource('accounts', AccountController::class);
    Route::apiResource('wilayas', WilayaController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('brands', BrandController::class);
    Route::apiResource('types', TypeController::class);
    Route::apiResource('colors', ColorController::class);
    Route::apiResource('clients', ClientController::class);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('inventories', InventoryController::class);
    Route::get('inventory-movements', [InventoryMovementController::class, 'index']);
    Route::apiResource('treasuries', TreasuryController::class);
    Route::apiResource('suppliers', SupplierController::class);
    Route::apiResource('transfers', TransferController::class);
    Route::post('transfers/{transfer}/receive', [TransferController::class, 'receive']);
    Route::apiResource('purchases', PurchaseController::class);
    Route::post('purchases/{purchase}/receive', [PurchaseController::class, 'receive']);
    //Route::get('purchases/{purchase}/payments', [PurchaseController::class, 'payments']);
    //Route::post('purchases/{purchase}/payments', [PurchaseController::class, 'addPayment']);
    Route::apiResource('stocks', StockController::class)->except(['update']);
    Route::apiResource('purchase.payments', PurchaseController::class)->only(['index', 'store']);
    Route::apiResource('purchases.returns', PurchaseReturnController::class)->parameters(['returns' => 'purchase_return']);
    Route::post('purchases/{purchase}/returns/{purchase_return}/approve', [PurchaseReturnController::class, 'approve']);

    Route::apiResource('restocks', RestockController::class);
    Route::post('restocks/{restock}/submit', [RestockController::class, 'submit']);
    Route::post('restocks/{restock}/cancel', [RestockController::class, 'cancel']);
    Route::post('restocks/{restock}/fulfill', [RestockController::class, 'fulfill']);

    Route::apiResource('stocks.prices', PriceController::class);
    Route::apiResource('stocks.batches', BatchController::class);
    Route::apiResource('expirations', ExpirationController::class);
    Route::post('expirations/{expiration}/approve', [ExpirationController::class, 'approve']);
    
});
