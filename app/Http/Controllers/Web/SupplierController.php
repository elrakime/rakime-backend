<?php

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Supplier\StoreSupplierRequest;
use App\Http\Requests\Web\Supplier\UpdateSupplierRequest;
use App\Http\Resources\Web\SupplierResource;
use App\Models\Supplier;
use App\Services\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function __construct(private readonly SupplierService $supplierService) {}

    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_SUPPLIERS->value)) {
            return $response;
        }

        return $this->successResponse(SupplierResource::collection($this->supplierService->list($request)));
    }

    public function store(StoreSupplierRequest $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::CREATE_SUPPLIERS->value)) {
            return $response;
        }

        try {
            $supplier = $this->supplierService->create($this->validateRequest($request));

            return $this->successResponse(new SupplierResource($supplier), statusCode: 201);
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function show(Supplier $supplier): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_SUPPLIERS->value)) {
            return $response;
        }

        try {
            return $this->successResponse(new SupplierResource($this->supplierService->show($supplier)));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::UPDATE_SUPPLIERS->value)) {
            return $response;
        }

        try {
            $supplier = $this->supplierService->update($supplier, $this->validateRequest($request));

            return $this->successResponse(new SupplierResource($supplier));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::DELETE_SUPPLIERS->value)) {
            return $response;
        }

        try {
            $this->supplierService->delete($supplier);

            return $this->successResponse(message: __('app.deleted'));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }
}
