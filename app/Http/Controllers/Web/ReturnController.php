<?php

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Return\StoreReturnRequest;
use App\Http\Requests\Web\Return\UpdateReturnRequest;
use App\Http\Resources\Web\ReturnResource;
use App\Models\PurchaseReturn;
use App\Services\ReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReturnController extends Controller
{
    public function __construct(private readonly ReturnService $purchaseReturnService) {}

    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_PURCHASES->value)) {
            return $response;
        }

        return $this->successResponse(
            ReturnResource::collection($this->purchaseReturnService->list($request)),
        );
    }

    public function store(StoreReturnRequest $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::APPROVE_PURCHASES->value)) {
            return $response;
        }

        try {
            $purchaseReturn = $this->purchaseReturnService->create($this->validateRequest($request));

            return $this->successResponse(new ReturnResource($purchaseReturn), statusCode: 201);
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function show(PurchaseReturn $purchaseReturn): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_PURCHASES->value)) {
            return $response;
        }

        try {
            return $this->successResponse(
                new ReturnResource($this->purchaseReturnService->show($purchaseReturn)),
            );
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function update(UpdateReturnRequest $request, PurchaseReturn $purchaseReturn): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::APPROVE_PURCHASES->value)) {
            return $response;
        }

        try {
            $purchaseReturn = $this->purchaseReturnService->update($purchaseReturn, $this->validateRequest($request));

            return $this->successResponse(new ReturnResource($purchaseReturn));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function destroy(PurchaseReturn $purchaseReturn): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::DELETE_PURCHASES->value)) {
            return $response;
        }

        try {
            $this->purchaseReturnService->delete($purchaseReturn);

            return $this->successResponse(message: __('app.deleted'));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function approve(PurchaseReturn $purchaseReturn): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::APPROVE_PURCHASES->value)) {
            return $response;
        }

        try {
            $purchaseReturn = $this->purchaseReturnService->approve($purchaseReturn);

            return $this->successResponse(new ReturnResource($purchaseReturn));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }
}
