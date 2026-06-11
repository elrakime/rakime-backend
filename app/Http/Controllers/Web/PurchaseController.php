<?php

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Purchase\ReceivePurchaseRequest;
use App\Http\Requests\Web\Purchase\StorePurchaseRequest;
use App\Http\Requests\Web\Purchase\UpdatePurchaseRequest;
use App\Http\Resources\Web\PurchaseResource;
use App\Models\Purchase;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function __construct(private readonly PurchaseService $purchaseService) {}

    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_PURCHASES->value)) {
            return $response;
        }

        return $this->successResponse(PurchaseResource::collection($this->purchaseService->list($request)));
    }

    public function store(StorePurchaseRequest $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::CREATE_PURCHASES->value)) {
            return $response;
        }

        try {
            $purchase = $this->purchaseService->create($this->validateRequest($request));

            return $this->successResponse(new PurchaseResource($purchase), statusCode: 201);
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function show(Purchase $purchase): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_PURCHASES->value)) {
            return $response;
        }

        try {
            return $this->successResponse(new PurchaseResource($this->purchaseService->show($purchase)));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function update(UpdatePurchaseRequest $request, Purchase $purchase): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::UPDATE_PURCHASES->value)) {
            return $response;
        }

        try {
            $purchase = $this->purchaseService->update($purchase, $this->validateRequest($request));

            return $this->successResponse(new PurchaseResource($purchase));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function destroy(Purchase $purchase): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::DELETE_PURCHASES->value)) {
            return $response;
        }

        try {
            $this->purchaseService->delete($purchase);

            return $this->successResponse(message: __('app.deleted'));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function receive(ReceivePurchaseRequest $request, Purchase $purchase): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::APPROVE_PURCHASES->value)) {
            return $response;
        }

        try {
            $purchase = $this->purchaseService->receive($purchase, $this->validateRequest($request));

            return $this->successResponse(new PurchaseResource($purchase));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }
}
