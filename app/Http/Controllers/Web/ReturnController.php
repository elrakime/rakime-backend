<?php

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Purchase\StorePurchaseReturnRequest;
use App\Http\Resources\Web\PurchaseReturnResource;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Services\PurchaseReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReturnController extends Controller
{
    public function __construct(private readonly PurchaseReturnService $purchaseReturnService) {}

    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_PURCHASES->value)) {
            return $response;
        }

        return $this->successResponse(
            PurchaseReturnResource::collection($this->purchaseReturnService->list($request)),
        );
    }

    public function store(StorePurchaseReturnRequest $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::APPROVE_PURCHASES->value)) {
            return $response;
        }

        $purchaseReturn = $this->purchaseReturnService->create($this->validateRequest($request));

        return $this->successResponse(new PurchaseReturnResource($purchaseReturn), statusCode: 201);
    }

    public function show(PurchaseReturn $purchaseReturn): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_PURCHASES->value)) {
            return $response;
        }

        return $this->successResponse(
            new PurchaseReturnResource($this->purchaseReturnService->show($purchaseReturn)),
        );
    }

    public function destroy(PurchaseReturn $purchaseReturn): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::DELETE_PURCHASES->value)) {
            return $response;
        }

        $this->purchaseReturnService->delete($purchaseReturn);

        return $this->successResponse(message: __('app.deleted'));
    }
}
