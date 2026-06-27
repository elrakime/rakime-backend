<?php

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Price\StorePriceRequest;
use App\Http\Requests\Web\Price\UpdatePriceRequest;
use App\Http\Resources\Web\PriceResource;
use App\Models\Price;
use App\Models\Stock;
use App\Services\PriceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PriceController extends Controller
{
    public function __construct(private readonly PriceService $priceService) {}

    public function index(Request $request, Stock $stock): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_PRICES->value)) {
            return $response;
        }

        $prices = $this->priceService->list($request, $stock);

        return $this->successResponse(PriceResource::collection($prices));
    }

    public function store(StorePriceRequest $request, Stock $stock): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::CREATE_PRICES->value)) {
            return $response;
        }

        $validated = $this->validateRequest($request);

        $price = $this->priceService->create($stock, $validated);

        return $this->successResponse(new PriceResource($price), statusCode: 201);
    }

    public function show(Stock $stock, Price $price): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_PRICES->value)) {
            return $response;
        }

        try {
            $price = $this->priceService->show($price);

            return $this->successResponse(new PriceResource($price));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function update(UpdatePriceRequest $request, Stock $stock, Price $price): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::UPDATE_PRICES->value)) {
            return $response;
        }

        try {
            $validated = $this->validateRequest($request);

            $price = $this->priceService->update($price, $validated);

            return $this->successResponse(new PriceResource($price));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function destroy(Stock $stock, Price $price): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::DELETE_PRICES->value)) {
            return $response;
        }

        try {
            $this->priceService->delete($price);

            return $this->successResponse(message: __('app.deleted'));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }
}
