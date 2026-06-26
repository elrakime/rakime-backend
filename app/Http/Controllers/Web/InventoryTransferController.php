<?php

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\InventoryTransfer\StoreInventoryTransferRequest;
use App\Http\Requests\Web\InventoryTransfer\UpdateInventoryTransferRequest;
use App\Http\Resources\Web\InventoryTransferResource;
use App\Models\InventoryTransfer;
use App\Services\InventoryTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryTransferController extends Controller
{
    public function __construct(private readonly InventoryTransferService $transferService) {}

    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_INVENTORY->value)) {
            return $response;
        }

        return $this->successResponse(
            InventoryTransferResource::collection($this->transferService->list($request)),
        );
    }

    public function store(StoreInventoryTransferRequest $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::MANAGE_INVENTORY->value)) {
            return $response;
        }

        try {
            $transfer = $this->transferService->create($this->validateRequest($request));

            return $this->successResponse(new InventoryTransferResource($transfer), statusCode: 201);
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function show(InventoryTransfer $transfer): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_INVENTORY->value)) {
            return $response;
        }

        try {
            return $this->successResponse(
                new InventoryTransferResource($this->transferService->show($transfer)),
            );
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function update(UpdateInventoryTransferRequest $request, InventoryTransfer $transfer): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::MANAGE_INVENTORY->value)) {
            return $response;
        }

        try {
            $transfer = $this->transferService->update($transfer, $this->validateRequest($request));

            return $this->successResponse(new InventoryTransferResource($transfer));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function destroy(InventoryTransfer $transfer): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::MANAGE_INVENTORY->value)) {
            return $response;
        }

        try {
            $this->transferService->delete($transfer);

            return $this->successResponse(message: __('app.deleted'));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function receive(InventoryTransfer $transfer): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::MANAGE_INVENTORY->value)) {
            return $response;
        }

        try {
            $transfer = $this->transferService->receive($transfer);

            return $this->successResponse(new InventoryTransferResource($transfer));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }
}
