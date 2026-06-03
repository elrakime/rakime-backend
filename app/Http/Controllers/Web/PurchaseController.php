<?php

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Purchase\ReceivePurchaseRequest;
use App\Http\Requests\Web\Purchase\StorePurchasePaymentRequest;
use App\Http\Requests\Web\Purchase\StorePurchaseRequest;
use App\Http\Requests\Web\Purchase\UpdatePurchaseRequest;
use App\Http\Resources\Web\PurchasePaymentResource;
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

        $purchase = $this->purchaseService->create($this->validateRequest($request));

        return $this->successResponse(new PurchaseResource($purchase), statusCode: 201);
    }

    public function show(Purchase $purchase): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_PURCHASES->value)) {
            return $response;
        }

        return $this->successResponse(new PurchaseResource($this->purchaseService->show($purchase)));
    }

    public function update(UpdatePurchaseRequest $request, Purchase $purchase): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::UPDATE_PURCHASES->value)) {
            return $response;
        }

        $purchase = $this->purchaseService->update($purchase, $this->validateRequest($request));

        return $this->successResponse(new PurchaseResource($purchase));
    }

    public function destroy(Purchase $purchase): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::DELETE_PURCHASES->value)) {
            return $response;
        }

        $this->purchaseService->delete($purchase);

        return $this->successResponse(message: __('app.deleted'));
    }

    public function receive(ReceivePurchaseRequest $request, Purchase $purchase): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::APPROVE_PURCHASES->value)) {
            return $response;
        }

        $purchase = $this->purchaseService->receive($purchase, $this->validateRequest($request));

        return $this->successResponse(new PurchaseResource($purchase));
    }

    public function payments(Purchase $purchase): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_PURCHASES->value)) {
            return $response;
        }

        $payments = $this->purchaseService->listPayments($purchase);

        return $this->successResponse(PurchasePaymentResource::collection($payments));
    }

    public function addPayment(StorePurchasePaymentRequest $request, Purchase $purchase): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::APPROVE_PURCHASES->value)) {
            return $response;
        }

        $payment = $this->purchaseService->addPayment($purchase, $this->validateRequest($request));

        return $this->successResponse(new PurchasePaymentResource($payment), statusCode: 201);
    }
}
