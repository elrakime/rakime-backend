<?php

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Inventory\StoreInventoryRequest;
use App\Http\Requests\Web\Inventory\UpdateInventoryRequest;
use App\Http\Resources\Web\InventoryResource;
use App\Models\Inventory;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(private readonly InventoryService $inventoryService) {}

    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_INVENTORY->value)) {
            return $response;
        }

        return $this->successResponse(InventoryResource::collection($this->inventoryService->list($request)));
    }

    public function store(StoreInventoryRequest $request): JsonResponse
    {
        if ($response = $this->authorizeBranchAccess($request->input('branch_id'))) {
            return $response;
        }

        if ($response = $this->authorizePermission(Permission::CREATE_INVENTORY->value)) {
            return $response;
        }

        $data = $this->validateRequest($request);

        try {
            $inventory = $this->inventoryService->create($data);

            return $this->successResponse(new InventoryResource($inventory), statusCode: 201);
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function show(Inventory $inventory): JsonResponse
    {
        if ($response = $this->authorizeBranchAccess($inventory)) {
            return $response;
        }

        if ($response = $this->authorizePermission(Permission::VIEW_INVENTORY->value)) {
            return $response;
        }

        try {
            return $this->successResponse(new InventoryResource($this->inventoryService->show($inventory)));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function update(UpdateInventoryRequest $request, Inventory $inventory): JsonResponse
    {
        if ($response = $this->authorizeBranchAccess($inventory)) {
            return $response;
        }

        if ($response = $this->authorizePermission(Permission::UPDATE_INVENTORY->value)) {
            return $response;
        }

        $data = $this->validateRequest($request);

        try {
            $inventory = $this->inventoryService->update($inventory, $data);

            return $this->successResponse(new InventoryResource($inventory));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function destroy(Inventory $inventory): JsonResponse
    {
        if ($response = $this->authorizeBranchAccess($inventory)) {
            return $response;
        }

        if ($response = $this->authorizePermission(Permission::DELETE_INVENTORY->value)) {
            return $response;
        }

        try {
            $this->inventoryService->delete($inventory);

            return $this->successResponse(message: __('app.deleted'));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }
}
