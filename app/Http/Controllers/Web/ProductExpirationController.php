<?php

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ProductExpiration\StoreProductExpirationRequest;
use App\Http\Requests\Web\ProductExpiration\UpdateProductExpirationRequest;
use App\Http\Resources\Web\ProductExpirationResource;
use App\Models\ProductExpiration;
use App\Services\ProductExpirationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductExpirationController extends Controller
{
    public function __construct(private readonly ProductExpirationService $expirationService) {}

    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_INVENTORY->value)) {
            return $response;
        }

        return $this->successResponse(
            ProductExpirationResource::collection($this->expirationService->list($request)),
        );
    }

    public function store(StoreProductExpirationRequest $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::MANAGE_INVENTORY->value)) {
            return $response;
        }

        $validated = $this->validateRequest($request);
        $validated['user_id'] = $request->user()->id;

        $expiration = $this->expirationService->create($validated);

        return $this->successResponse(new ProductExpirationResource($expiration), statusCode: 201);
    }

    public function show(ProductExpiration $expiration): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_INVENTORY->value)) {
            return $response;
        }

        return $this->successResponse(
            new ProductExpirationResource($this->expirationService->show($expiration)),
        );
    }

    public function update(UpdateProductExpirationRequest $request, ProductExpiration $expiration): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::MANAGE_INVENTORY->value)) {
            return $response;
        }

        $expiration = $this->expirationService->update($expiration, $this->validateRequest($request));

        return $this->successResponse(new ProductExpirationResource($expiration));
    }

    public function destroy(ProductExpiration $expiration): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::MANAGE_INVENTORY->value)) {
            return $response;
        }

        $this->expirationService->delete($expiration);

        return $this->successResponse(message: __('app.deleted'));
    }
}
