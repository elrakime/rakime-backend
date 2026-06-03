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
        if ($response = $this->authorizePermission(Permission::VIEW_PURCHASES->value)) {
            return $response;
        }

        return $this->successResponse(SupplierResource::collection($this->supplierService->list($request)));
    }

    public function store(StoreSupplierRequest $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::CREATE_PURCHASES->value)) {
            return $response;
        }

        $supplier = $this->supplierService->create($this->validateRequest($request));

        return $this->successResponse(new SupplierResource($supplier), statusCode: 201);
    }

    public function show(Supplier $supplier): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_PURCHASES->value)) {
            return $response;
        }

        return $this->successResponse(new SupplierResource($this->supplierService->show($supplier)));
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::UPDATE_PURCHASES->value)) {
            return $response;
        }

        $supplier = $this->supplierService->update($supplier, $this->validateRequest($request));

        return $this->successResponse(new SupplierResource($supplier));
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::DELETE_PURCHASES->value)) {
            return $response;
        }

        $this->supplierService->delete($supplier);

        return $this->successResponse(message: __('app.deleted'));
    }
}
