<?php

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Type\StoreTypeRequest;
use App\Http\Requests\Web\Type\UpdateTypeRequest;
use App\Http\Resources\Web\TypeResource;
use App\Models\Type;
use App\Services\TypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TypeController extends Controller
{
    public function __construct(private readonly TypeService $typeService) {}

    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_TYPES->value)) {
            return $response;
        }

        return $this->successResponse(TypeResource::collection($this->typeService->list($request)));
    }

    public function store(StoreTypeRequest $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::CREATE_TYPES->value)) {
            return $response;
        }

        try {
            $type = $this->typeService->create($this->validateRequest($request));

            return $this->successResponse(new TypeResource($type), statusCode: 201);
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function show(Type $type): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_TYPES->value)) {
            return $response;
        }

        try {
            return $this->successResponse(new TypeResource($this->typeService->show($type)));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function update(UpdateTypeRequest $request, Type $type): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::UPDATE_TYPES->value)) {
            return $response;
        }

        try {
            $type = $this->typeService->update($type, $this->validateRequest($request));

            return $this->successResponse(new TypeResource($type));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function destroy(Type $type): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::DELETE_TYPES->value)) {
            return $response;
        }

        try {
            $this->typeService->delete($type);

            return $this->successResponse(message: __('app.deleted'));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }
}
