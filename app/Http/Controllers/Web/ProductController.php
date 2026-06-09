<?php

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Product\StoreProductRequest;
use App\Http\Requests\Web\Product\UpdateProductRequest;
use App\Http\Resources\Web\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $productService) {}

    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_PRODUCTS->value)) {
            return $response;
        }

        return $this->successResponse(ProductResource::collection($this->productService->list($request)));
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::CREATE_PRODUCTS->value)) {
            return $response;
        }

        try {
            $product = $this->productService->create($this->validateRequest($request), $request);

            return $this->successResponse(new ProductResource($product), statusCode: 201);
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function show(Product $product): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_PRODUCTS->value)) {
            return $response;
        }

        try {
            return $this->successResponse(new ProductResource($this->productService->show($product)));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::UPDATE_PRODUCTS->value)) {
            return $response;
        }

        try {
            $product = $this->productService->update($product, $this->validateRequest($request), $request);

            return $this->successResponse(new ProductResource($product));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function destroy(Product $product): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::DELETE_PRODUCTS->value)) {
            return $response;
        }

        try {
            $this->productService->delete($product);

            return $this->successResponse(message: __('app.deleted'));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }
}
