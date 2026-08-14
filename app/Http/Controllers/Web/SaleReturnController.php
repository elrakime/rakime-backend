<?php

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Enums\SaleReturnStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\SaleReturn\ApproveSaleReturnRequest;
use App\Http\Requests\Web\SaleReturn\StoreSaleReturnRequest;
use App\Http\Requests\Web\SaleReturn\UpdateSaleReturnRequest;
use App\Http\Resources\Web\SaleReturnResource;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Services\SaleReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleReturnController extends Controller
{
    public function __construct(private readonly SaleReturnService $saleReturnService) {}

    public function index(Request $request, Sale $sale): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_SALE_RETURNS->value)) {
            return $response;
        }

        return $this->successResponse(
            SaleReturnResource::collection(
                $this->saleReturnService->list($request, $sale)
            ),
        );
    }

    public function store(StoreSaleReturnRequest $request, Sale $sale): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::CREATE_SALE_RETURNS->value)) {
            return $response;
        }

        try {
            $saleReturn = $this->saleReturnService->create(
                $sale,
                $this->validateRequest($request)
            );

            return $this->successResponse(
                new SaleReturnResource($saleReturn),
                statusCode: 201
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                message: $e->getMessage(),
                statusCode: $e->getCode() ?: 400
            );
        }
    }

    public function show(Sale $sale, SaleReturn $saleReturn): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_SALE_RETURNS->value)) {
            return $response;
        }

        try {
            return $this->successResponse(
                new SaleReturnResource(
                    $this->saleReturnService->show($saleReturn)
                ),
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                message: $e->getMessage(),
                statusCode: $e->getCode() ?: 400
            );
        }
    }

    public function update(UpdateSaleReturnRequest $request, Sale $sale, SaleReturn $saleReturn): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::UPDATE_SALE_RETURNS->value)) {
            return $response;
        }

        try {
            $saleReturn = $this->saleReturnService->update(
                $saleReturn,
                $this->validateRequest($request)
            );

            return $this->successResponse(
                new SaleReturnResource($saleReturn)
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                message: $e->getMessage(),
                statusCode: $e->getCode() ?: 400
            );
        }
    }

    public function destroy(Sale $sale, SaleReturn $saleReturn): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::DELETE_SALE_RETURNS->value)) {
            return $response;
        }

        try {
            $this->saleReturnService->delete($saleReturn);

            return $this->successResponse(message: __('app.deleted'));
        } catch (\Exception $e) {
            return $this->errorResponse(
                message: $e->getMessage(),
                statusCode: $e->getCode() ?: 400
            );
        }
    }

    public function approve(ApproveSaleReturnRequest $request, Sale $sale, SaleReturn $saleReturn): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::APPROVE_SALE_RETURNS->value)) {
            return $response;
        }

        try {
            $data = $this->validateRequest($request);

            $saleReturn->assertCanTransitionTo(SaleReturnStatus::COMPLETED->value);

            $saleReturn = $this->saleReturnService->approve($saleReturn, $data['wallet_id']);

            return $this->successResponse(
                new SaleReturnResource($saleReturn)
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                message: $e->getMessage(),
                statusCode: $e->getCode() ?: 400
            );
        }
    }

    public function cancel(Sale $sale, SaleReturn $saleReturn): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::CANCEL_SALE_RETURNS->value)) {
            return $response;
        }

        try {
            $saleReturn->assertCanTransitionTo(SaleReturnStatus::CANCELED->value);

            $saleReturn = $this->saleReturnService->cancel($saleReturn);

            return $this->successResponse(
                new SaleReturnResource($saleReturn)
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                message: $e->getMessage(),
                statusCode: $e->getCode() ?: 400
            );
        }
    }
}
