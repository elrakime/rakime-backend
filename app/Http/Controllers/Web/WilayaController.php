<?php

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Wilaya\StoreWilayaRequest;
use App\Http\Requests\Web\Wilaya\UpdateWilayaRequest;
use App\Http\Resources\Web\WilayaResource;
use App\Models\Wilaya;
use App\Services\WilayaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WilayaController extends Controller
{
    public function __construct(private readonly WilayaService $wilayaService) {}

    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_WILAYAS->value)) {
            return $response;
        }

        return $this->successResponse(WilayaResource::collection($this->wilayaService->list($request)));
    }

    public function store(StoreWilayaRequest $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::CREATE_WILAYAS->value)) {
            return $response;
        }

        try {
            $wilaya = $this->wilayaService->create($this->validateRequest($request));

            return $this->successResponse(new WilayaResource($wilaya), statusCode: 201);
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function show(Wilaya $wilaya): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_WILAYAS->value)) {
            return $response;
        }

        try {
            return $this->successResponse(new WilayaResource($this->wilayaService->show($wilaya)));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function update(UpdateWilayaRequest $request, Wilaya $wilaya): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::UPDATE_WILAYAS->value)) {
            return $response;
        }

        try {
            $wilaya = $this->wilayaService->update($wilaya, $this->validateRequest($request));

            return $this->successResponse(new WilayaResource($wilaya));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function destroy(Wilaya $wilaya): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::DELETE_WILAYAS->value)) {
            return $response;
        }

        try {
            $this->wilayaService->delete($wilaya);

            return $this->successResponse(message: __('app.deleted'));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }
}
