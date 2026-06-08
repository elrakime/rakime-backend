<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StorePriceRequest;
use App\Http\Requests\Api\UpdatePriceRequest;
use App\Http\Resources\Api\PriceResource;
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
        $prices = $this->priceService->list($request, $stock);

        return $this->successResponse(PriceResource::collection($prices));
    }

    public function store(StorePriceRequest $request, Stock $stock): JsonResponse
    {
        $validated = $this->validateRequest($request);

        $price = $this->priceService->create($stock, $validated);

        return $this->successResponse(
            new PriceResource($price),
            __('http-statuses.201'),
            201,
        );
    }

    public function show(Stock $stock, Price $price): JsonResponse
    {
        $price = $this->priceService->show($price);

        return $this->successResponse(new PriceResource($price));
    }

    public function update(UpdatePriceRequest $request, Stock $stock, Price $price): JsonResponse
    {
        $validated = $this->validateRequest($request);

        $price = $this->priceService->update($price, $validated);

        return $this->successResponse(new PriceResource($price));
    }

    public function destroy(Stock $stock, Price $price): JsonResponse
    {
        $this->priceService->delete($price);

        return $this->successResponse(null, __('http-statuses.200'));
    }
}
