<?php

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Expiration\StoreExpirationRequest;
use App\Http\Requests\Web\Expiration\UpdateExpirationRequest;
use App\Http\Resources\Web\ExpirationResource;
use App\Models\Expiration;
use App\Services\ExpirationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpirationController extends Controller
{
    public function __construct(private readonly ExpirationService $expirationService) {}

    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_EXPIRATIONS->value)) {
            return $response;
        }

        return $this->successResponse(
            ExpirationResource::collection($this->expirationService->list($request)),
        );
    }

    public function store(StoreExpirationRequest $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::CREATE_EXPIRATIONS->value)) {
            return $response;
        }

        try {
            $validated = $this->validateRequest($request);
            $validated['user_id'] = $request->user()->id;

            $expiration = $this->expirationService->create($validated);

            return $this->successResponse(new ExpirationResource($expiration), statusCode: 201);
        } catch (\Exception $e) {
            return $this->errorResponse(message:$e->getMessage(), statusCode: $e->getCode() ?? 400);
        }

    }

    public function show(Expiration $expiration): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_EXPIRATIONS->value)) {
            return $response;
        }

        try {
            return $this->successResponse(
                new ExpirationResource($this->expirationService->show($expiration)),
            );
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function update(UpdateExpirationRequest $request, Expiration $expiration): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::UPDATE_EXPIRATIONS->value)) {
            return $response;
        }

        try {
            $expiration = $this->expirationService->update($expiration, $this->validateRequest($request));

            return $this->successResponse(new ExpirationResource($expiration));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function destroy(Expiration $expiration): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::DELETE_EXPIRATIONS->value)) {
            return $response;
        }

        try {
            $this->expirationService->delete($expiration);

            return $this->successResponse(message: __('app.deleted'));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function approve(Expiration $expiration): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::APPROVE_EXPIRATIONS->value)) {
            return $response;
        }

        try {
            $expiration = $this->expirationService->approve($expiration);

            return $this->successResponse(new ExpirationResource($expiration));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }
}
