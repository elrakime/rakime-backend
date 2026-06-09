<?php

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Transfer\StoreTransferRequest;
use App\Http\Requests\Web\Transfer\UpdateTransferRequest;
use App\Http\Resources\Web\TransferResource;
use App\Models\Transfer;
use App\Services\TransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransferController extends Controller
{
    public function __construct(private readonly TransferService $transferService) {}

    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_INVENTORY->value)) {
            return $response;
        }

        return $this->successResponse(
            TransferResource::collection($this->transferService->list($request)),
        );
    }

    public function store(StoreTransferRequest $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::MANAGE_INVENTORY->value)) {
            return $response;
        }

        try {
            $transfer = $this->transferService->create($this->validateRequest($request));

            return $this->successResponse(new TransferResource($transfer), statusCode: 201);
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function show(Transfer $transfer): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_INVENTORY->value)) {
            return $response;
        }

        try {
            return $this->successResponse(
                new TransferResource($this->transferService->show($transfer)),
            );
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function update(UpdateTransferRequest $request, Transfer $transfer): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::MANAGE_INVENTORY->value)) {
            return $response;
        }

        try {
            $transfer = $this->transferService->update($transfer, $this->validateRequest($request));

            return $this->successResponse(new TransferResource($transfer));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function destroy(Transfer $transfer): JsonResponse
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

    public function receive(Transfer $transfer): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::MANAGE_INVENTORY->value)) {
            return $response;
        }

        try {
            $transfer = $this->transferService->receive($transfer);

            return $this->successResponse(new TransferResource($transfer));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }
}
