<?php

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\WalletTransfer\StoreWalletTransferRequest;
use App\Http\Resources\Web\WalletTransferResource;
use App\Models\WalletTransfer;
use App\Services\WalletTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletTransferController extends Controller
{
    public function __construct(private readonly WalletTransferService $walletTransferService) {}

    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_WALLET->value)) {
            return $response;
        }

        return $this->successResponse(
            WalletTransferResource::collection($this->walletTransferService->list($request)),
        );
    }

    public function store(StoreWalletTransferRequest $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::MANAGE_WALLET->value)) {
            return $response;
        }

        $data = $this->validateRequest($request);

        $data['performed_by'] = $request->user()?->id;

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

    public function show(WalletTransfer $walletTransfer): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_WALLET->value)) {
            return $response;
        }

        try {
            return $this->successResponse(
                new WalletTransferResource($this->walletTransferService->show($walletTransfer)),
            );
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?: 400);
        }
    }

    public function destroy(WalletTransfer $walletTransfer): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::MANAGE_WALLET->value)) {
            return $response;
        }

        try {
            $this->walletTransferService->delete($walletTransfer);

            return $this->successResponse(message: __('app.deleted'));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?: 400);
        }
    }
}
