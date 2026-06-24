<?php

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\PurchaseReturn\StorePurchaseReturnRequest;
use App\Http\Requests\Web\PurchaseReturn\UpdatePurchaseReturnRequest;
use App\Http\Resources\Web\PurchaseReturnResource;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Services\PurchaseReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseReturnController extends Controller
{
    public function __construct(private readonly PurchaseReturnService $purchaseReturnService) {}

    public function index(Request $request, Purchase $purchase): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_PURCHASE_RETURNS->value)) {
            return $response;
        }

        return $this->successResponse(
            PurchaseReturnResource::collection(
                $this->purchaseReturnService->list($request, $purchase)
            ),
        );
    }

    public function store(StorePurchaseReturnRequest $request, Purchase $purchase): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::CREATE_PURCHASE_RETURNS->value)) {
            return $response;
        }

        try {
            $purchaseReturn = $this->purchaseReturnService->create(
                $purchase,
                $this->validateRequest($request)
            );

            return $this->successResponse(
                new PurchaseReturnResource($purchaseReturn),
                statusCode: 201
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                message: $e->getMessage(),
                statusCode: $e->getCode() ?: 400
            );
        }
    }

    public function show(Purchase $purchase, PurchaseReturn $purchaseReturn): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_PURCHASE_RETURNS->value)) {
            return $response;
        }

        try {
            return $this->successResponse(
                new PurchaseReturnResource(
                    $this->purchaseReturnService->show($purchaseReturn)
                ),
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                message: $e->getMessage(),
                statusCode: $e->getCode() ?: 400
            );
        }
    }

    public function update(UpdatePurchaseReturnRequest $request, Purchase $purchase, PurchaseReturn $purchaseReturn): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::UPDATE_PURCHASE_RETURNS->value)) {
            return $response;
        }

        try {
            $purchaseReturn = $this->purchaseReturnService->update(
                $purchaseReturn,
                $this->validateRequest($request)
            );

            return $this->successResponse(
                new PurchaseReturnResource($purchaseReturn)
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                message: $e->getMessage(),
                statusCode: $e->getCode() ?: 400
            );
        }
    }

    public function destroy(Purchase $purchase, PurchaseReturn $purchaseReturn): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::DELETE_PURCHASE_RETURNS->value)) {
            return $response;
        }

        try {
            $this->purchaseReturnService->delete($purchaseReturn);

            return $this->successResponse(message: __('app.deleted'));
        } catch (\Exception $e) {
            return $this->errorResponse(
                message: $e->getMessage(),
                statusCode: $e->getCode() ?: 400
            );
        }
    }

    public function approve(Purchase $purchase, PurchaseReturn $purchaseReturn): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::APPROVE_PURCHASE_RETURNS->value)) {
            return $response;
        }

        try {
            $purchaseReturn = $this->purchaseReturnService->approve($purchaseReturn);

            return $this->successResponse(
                new PurchaseReturnResource($purchaseReturn)
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                message: $e->getMessage(),
                statusCode: $e->getCode() ?: 400
            );
        }
    }
}
