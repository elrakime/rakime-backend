<?php

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\PurchasePayment\StorePurchasePaymentRequest;
use App\Http\Resources\Web\PurchasePaymentResource;
use App\Models\Purchase;
use App\Services\PurchasePaymentService;
use Illuminate\Http\JsonResponse;

class PurchasePaymentController extends Controller
{
    public function __construct(private readonly PurchasePaymentService $purchasePaymentService) {}

    public function index(Purchase $purchase): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_PURCHASES->value)) {
            return $response;
        }

        try {
            $payments = $this->purchasePaymentService->list($purchase);

            return $this->successResponse(PurchasePaymentResource::collection($payments));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?: 400);
        }
    }

    public function store(StorePurchasePaymentRequest $request, Purchase $purchase): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::APPROVE_PURCHASES->value)) {
            return $response;
        }

        try {
            $payment = $this->purchasePaymentService->create($purchase, $this->validateRequest($request));

            return $this->successResponse(new PurchasePaymentResource($payment), statusCode: 201);
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?: 400);
        }
    }
}
