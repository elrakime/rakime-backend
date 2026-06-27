<?php

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Stock\StoreStockRequest;
use App\Http\Resources\Web\StockResource;
use App\Models\Stock;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function __construct(private readonly StockService $stockService) {}

    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_STOCKS->value)) {
            return $response;
        }

        return $this->successResponse(StockResource::collection($this->stockService->list($request)));
    }

    public function store(StoreStockRequest $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::CREATE_STOCKS->value)) {
            return $response;
        }

        try {
            $stock = $this->stockService->create($this->validateRequest($request), $request);

            return $this->successResponse(new StockResource($stock), statusCode: 201);
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function show(Stock $stock): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_STOCKS->value)) {
            return $response;
        }

        try {
            return $this->successResponse(new StockResource($this->stockService->show($stock)));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function destroy(Stock $stock): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::DELETE_STOCKS->value)) {
            return $response;
        }

        try {
            $this->stockService->delete($stock);

            return $this->successResponse(message: __('app.deleted'));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }
}
