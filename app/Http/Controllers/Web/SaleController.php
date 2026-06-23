<?php

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Sale\StoreSaleRequest;
use App\Http\Requests\Web\Sale\UpdateSaleRequest;
use App\Http\Resources\Web\SaleResource;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function __construct(private readonly SaleService $saleService) {}

    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_SALES->value)) {
            return $response;
        }

        return $this->successResponse(
            SaleResource::collection($this->saleService->list($request)),
        );
    }

    public function store(StoreSaleRequest $request): JsonResponse
    {
        if ($response = $this->authorizeBranchAccess($request->input('branch_id'))) {
            return $response;
        }

        if ($response = $this->authorizePermission(Permission::CREATE_SALES->value)) {
            return $response;
        }

        $data = $this->validateRequest($request);

        try {
            $sale = $this->saleService->create($data);

            return $this->successResponse(new SaleResource($sale), statusCode: 201);
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?: 400);
        }
    }

    public function show(Sale $sale): JsonResponse
    {
        if ($response = $this->authorizeBranchAccess($sale)) {
            return $response;
        }

        if ($response = $this->authorizePermission(Permission::VIEW_SALES->value)) {
            return $response;
        }

        try {
            return $this->successResponse(
                new SaleResource($this->saleService->show($sale)),
            );
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?: 400);
        }
    }

    public function update(UpdateSaleRequest $request, Sale $sale): JsonResponse
    {
        if ($response = $this->authorizeBranchAccess($sale)) {
            return $response;
        }

        if ($response = $this->authorizePermission(Permission::UPDATE_SALES->value)) {
            return $response;
        }

        $data = $this->validateRequest($request);

        try {
            $sale = $this->saleService->update($sale, $data);

            return $this->successResponse(new SaleResource($sale));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?: 400);
        }
    }

    public function destroy(Sale $sale): JsonResponse
    {
        if ($response = $this->authorizeBranchAccess($sale)) {
            return $response;
        }

        if ($response = $this->authorizePermission(Permission::DELETE_SALES->value)) {
            return $response;
        }

        try {
            $this->saleService->delete($sale);

            return $this->successResponse(message: __('app.deleted'));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?: 400);
        }
    }
}
