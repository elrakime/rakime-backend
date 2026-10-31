<?php

namespace App\Http\Controllers\Api;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\WalletTransfer\StoreWalletTransferRequest;
use App\Http\Resources\Web\WalletTransferResource;
use App\Services\WalletTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletTransferController extends Controller
{
    public function __construct(private readonly WalletTransferService $walletTransferService) {}

    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_WALLET_TRANSFERS->value)) {
            return $response;
        }

        return $this->successResponse(
            WalletTransferResource::collection($this->walletTransferService->list($request)),
        );
    }

    public function store(StoreWalletTransferRequest $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::CREATE_WALLET_TRANSFERS->value)) {
            return $response;
        }

        $data = $this->validateRequest($request);

        try {
            $transfer = $this->walletTransferService->create($data);

            return $this->successResponse(
                new WalletTransferResource($transfer),
                statusCode: 201,
            );
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?: 400);
        }
    }
}
