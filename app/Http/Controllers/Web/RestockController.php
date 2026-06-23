<?php

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Restock\FulfillRestockRequest;
use App\Http\Requests\Web\Restock\StoreRestockRequest;
use App\Http\Requests\Web\Restock\UpdateRestockRequest;
use App\Http\Resources\Web\RestockResource;
use App\Models\Restock;
use App\Services\RestockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RestockController extends Controller
{
    public function __construct(private readonly RestockService $restockService) {}

    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_RESTOCKS->value)) {
            return $response;
        }

        return $this->successResponse(
            RestockResource::collection($this->restockService->list($request)),
        );
    }

    public function store(StoreRestockRequest $request): JsonResponse
    {
        if ($response = $this->authorizeBranchAccess($request->input('branch_id'))) {
            return $response;
        }

        if ($response = $this->authorizePermission(Permission::CREATE_RESTOCKS->value)) {
            return $response;
        }

        $data = $this->validateRequest($request);

        try {
            $restock = $this->restockService->create($data);

            return $this->successResponse(new RestockResource($restock), statusCode: 201);
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?: 400);
        }
    }

    public function show(Restock $restock): JsonResponse
    {
        if ($response = $this->authorizeBranchAccess($restock)) {
            return $response;
        }

        if ($response = $this->authorizePermission(Permission::VIEW_RESTOCKS->value)) {
            return $response;
        }

        try {
            return $this->successResponse(
                new RestockResource($this->restockService->show($restock)),
            );
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?: 400);
        }
    }

    public function update(UpdateRestockRequest $request, Restock $restock): JsonResponse
    {
        if ($response = $this->authorizeBranchAccess($restock)) {
            return $response;
        }

        if ($response = $this->authorizePermission(Permission::UPDATE_RESTOCKS->value)) {
            return $response;
        }

        $data = $this->validateRequest($request);

        try {
            $restock = $this->restockService->update($restock, $data);

            return $this->successResponse(new RestockResource($restock));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?: 400);
        }
    }

    public function destroy(Restock $restock): JsonResponse
    {
        if ($response = $this->authorizeBranchAccess($restock)) {
            return $response;
        }

        if ($response = $this->authorizePermission(Permission::DELETE_RESTOCKS->value)) {
            return $response;
        }

        try {
            $this->restockService->delete($restock);

            return $this->successResponse(message: __('app.deleted'));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?: 400);
        }
    }

    public function submit(Restock $restock): JsonResponse
    {
        if ($response = $this->authorizeBranchAccess($restock)) {
            return $response;
        }

        if ($response = $this->authorizePermission(Permission::APPROVE_RESTOCKS->value)) {
            return $response;
        }

        try {
            $restock = $this->restockService->submit($restock);

            return $this->successResponse(new RestockResource($restock));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?: 400);
        }
    }

    public function cancel(Restock $restock): JsonResponse
    {
        if ($response = $this->authorizeBranchAccess($restock)) {
            return $response;
        }

        if ($response = $this->authorizePermission(Permission::APPROVE_RESTOCKS->value)) {
            return $response;
        }

        try {
            $restock = $this->restockService->cancel($restock);

            return $this->successResponse(new RestockResource($restock));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?: 400);
        }
    }

    public function fulfill(FulfillRestockRequest $request, Restock $restock): JsonResponse
    {
        if ($response = $this->authorizeBranchAccess($restock)) {
            return $response;
        }

        if ($response = $this->authorizePermission(Permission::APPROVE_RESTOCKS->value)) {
            return $response;
        }

        $data = $this->validateRequest($request);

        try {
            $restock = $this->restockService->fulfill($restock, $data);

            return $this->successResponse(new RestockResource($restock));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?: 400);
        }
    }
}
