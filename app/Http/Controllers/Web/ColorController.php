<?php

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Color\StoreColorRequest;
use App\Http\Requests\Web\Color\UpdateColorRequest;
use App\Http\Resources\Web\ColorResource;
use App\Models\Color;
use App\Services\ColorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ColorController extends Controller
{
    public function __construct(private readonly ColorService $colorService) {}

    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_COLORS->value)) {
            return $response;
        }

        return $this->successResponse(ColorResource::collection($this->colorService->list($request)));
    }

    public function store(StoreColorRequest $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::CREATE_COLORS->value)) {
            return $response;
        }

        try {
            $color = $this->colorService->create($this->validateRequest($request));

            return $this->successResponse(new ColorResource($color), statusCode: 201);
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function show(Color $color): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_COLORS->value)) {
            return $response;
        }

        try {
            return $this->successResponse(new ColorResource($this->colorService->show($color)));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function update(UpdateColorRequest $request, Color $color): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::UPDATE_COLORS->value)) {
            return $response;
        }

        try {
            $color = $this->colorService->update($color, $this->validateRequest($request));

            return $this->successResponse(new ColorResource($color));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function destroy(Color $color): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::DELETE_COLORS->value)) {
            return $response;
        }

        try {
            $this->colorService->delete($color);

            return $this->successResponse(message: __('app.deleted'));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }
}
