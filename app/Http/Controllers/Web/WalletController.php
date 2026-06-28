<?php

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Enums\WalletMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Wallet\DepositWalletRequest;
use App\Http\Requests\Web\Wallet\StoreWalletRequest;
use App\Http\Requests\Web\Wallet\UpdateWalletRequest;
use App\Http\Requests\Web\Wallet\WithdrawWalletRequest;
use App\Http\Resources\Web\WalletResource;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(private readonly WalletService $walletService) {}

    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_WALLET->value)) {
            return $response;
        }

        return $this->successResponse(WalletResource::collection($this->walletService->list($request)));
    }

    public function store(StoreWalletRequest $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::CREATE_WALLET->value)) {
            return $response;
        }

        $data = $this->validateRequest($request);

        try {
            $wallet = $this->walletService->create($data);

            return $this->successResponse(new WalletResource($wallet), statusCode: 201);
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function show(Wallet $wallet): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_WALLET->value)) {
            return $response;
        }

        try {
            return $this->successResponse(new WalletResource($this->walletService->show($wallet)));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function update(UpdateWalletRequest $request, Wallet $wallet): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::UPDATE_WALLET->value)) {
            return $response;
        }

        $data = $this->validateRequest($request);

        try {
            $wallet = $this->walletService->update($wallet, $data);

            return $this->successResponse(new WalletResource($wallet));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function destroy(Wallet $wallet): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::DELETE_WALLET->value)) {
            return $response;
        }

        try {
            $this->walletService->delete($wallet);

            return $this->successResponse(message: __('app.deleted'));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function deposit(DepositWalletRequest $request, Wallet $wallet): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::WALLET_DEPOSIT->value)) {
            return $response;
        }

        $data = $this->validateRequest($request);

        try {
            $method = match ($data['type']) {
                WalletMovementType::ADJUSTMENT->value => 'adjustment',
                default                               => 'deposit',
            };

            $movement = $this->walletService->{$method}(
                wallet: $wallet,
                amount: $data['amount'],
                note: $data['note'] ?? null,
                performedBy: $request->user()?->id,
            );

            return $this->successResponse(new WalletResource($wallet->fresh()->loadMissing('owner')));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?: 400);
        }
    }

    public function withdraw(WithdrawWalletRequest $request, Wallet $wallet): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::WALLET_WITHDRAW->value)) {
            return $response;
        }

        $data = $this->validateRequest($request);

        try {
            $method = match ($data['type']) {
                WalletMovementType::WITHDRAWAL->value => 'withdraw',
                WalletMovementType::EXPENSE->value    => 'expense',
                WalletMovementType::SALARY->value     => 'salary',
                WalletMovementType::ADJUSTMENT->value => 'adjustment',
                default                               => 'withdraw',
            };

            $movement = $this->walletService->{$method}(
                wallet: $wallet,
                amount: $data['amount'],
                note: $data['note'] ?? null,
                performedBy: $request->user()?->id,
            );

            return $this->successResponse(new WalletResource($wallet->fresh()->loadMissing('owner')));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?: 400);
        }
    }
}
