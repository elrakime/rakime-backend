<?php

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Brand\StoreBrandRequest;
use App\Http\Requests\Web\Brand\UpdateBrandRequest;
use App\Http\Resources\Web\BrandResource;
use App\Models\Brand;
use App\Services\BrandService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function __construct(private readonly BrandService $brandService) {}

    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_BRANDS->value)) {
            return $response;
        }

        return $this->successResponse(BrandResource::collection($this->brandService->list($request)));
    }

    public function store(StoreBrandRequest $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::CREATE_BRANDS->value)) {
            return $response;
        }

        try {
            $brand = $this->brandService->create($this->validateRequest($request), $request);

            return $this->successResponse(new BrandResource($brand), statusCode: 201);
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function show(Brand $brand): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_BRANDS->value)) {
            return $response;
        }

        try {
            return $this->successResponse(new BrandResource($this->brandService->show($brand)));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function update(UpdateBrandRequest $request, Brand $brand): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::UPDATE_BRANDS->value)) {
            return $response;
        }

        try {
            $brand = $this->brandService->update($brand, $this->validateRequest($request), $request);

            return $this->successResponse(new BrandResource($brand));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function destroy(Brand $brand): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::DELETE_BRANDS->value)) {
            return $response;
        }

        try {
            $this->brandService->delete($brand);

            return $this->successResponse(message: __('app.deleted'));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }
}
