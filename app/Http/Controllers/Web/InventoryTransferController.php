<?php

namespace App\Http\Controllers\Web;

use App\Enums\InventoryTransferStatus;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\InventoryTransfer\ReceiveInventoryTransferRequest;
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
        if ($response = $this->authorizePermission(Permission::VIEW_INVENTORY_TRANSFERS->value)) {
            return $response;
        }

        return $this->successResponse(
            InventoryTransferResource::collection($this->transferService->list($request)),
        );
    }

    public function store(StoreInventoryTransferRequest $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::CREATE_INVENTORY_TRANSFERS->value)) {
            return $response;
        }

        try {
            $transfer = $this->transferService->create($this->validateRequest($request));

            return $this->successResponse(new InventoryTransferResource($transfer), statusCode: 201);
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function show(InventoryTransfer $inventory_transfer): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_INVENTORY_TRANSFERS->value)) {
            return $response;
        }

        try {
            return $this->successResponse(
                new InventoryTransferResource($this->transferService->show($inventory_transfer)),
            );
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function update(UpdateInventoryTransferRequest $request, InventoryTransfer $inventory_transfer): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::UPDATE_INVENTORY_TRANSFERS->value)) {
            return $response;
        }

        try {
            $inventory_transfer = $this->transferService->update($inventory_transfer, $this->validateRequest($request));

            return $this->successResponse(new InventoryTransferResource($inventory_transfer));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function destroy(InventoryTransfer $inventory_transfer): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::DELETE_INVENTORY_TRANSFERS->value)) {
            return $response;
        }

        try {
            $this->transferService->delete($inventory_transfer);

            return $this->successResponse(message: __('app.deleted'));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function dispatch(InventoryTransfer $inventory_transfer): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::DISPATCH_INVENTORY_TRANSFERS->value)) {
            return $response;
        }

        try {
            $inventory_transfer->assertCanTransitionTo(InventoryTransferStatus::DISPATCHED->value);

            $inventory_transfer = $this->transferService->dispatch($inventory_transfer);

            return $this->successResponse(new InventoryTransferResource($inventory_transfer));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function receive(ReceiveInventoryTransferRequest $request, InventoryTransfer $inventory_transfer): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::RECEIVE_INVENTORY_TRANSFERS->value)) {
            return $response;
        }

        try {
            $inventory_transfer->assertCanTransitionTo(InventoryTransferStatus::RECEIVED->value);

            $inventory_transfer = $this->transferService->receive($inventory_transfer, $this->validateRequest($request));

            return $this->successResponse(new InventoryTransferResource($inventory_transfer));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function cancel(InventoryTransfer $inventory_transfer): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::CANCEL_INVENTORY_TRANSFERS->value)) {
            return $response;
        }

        try {
            $inventory_transfer->assertCanTransitionTo(InventoryTransferStatus::CANCELED->value);

            $inventory_transfer = $this->transferService->cancel($inventory_transfer);

            return $this->successResponse(new InventoryTransferResource($inventory_transfer));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }
}
