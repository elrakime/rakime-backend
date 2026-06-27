<?php

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Resources\Web\InventoryMovementResource;
use App\Services\InventoryMovementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryMovementController extends Controller
{
    public function __construct(private readonly InventoryMovementService $inventoryMovementService) {}

    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_INVENTORY_MOVEMENTS->value)) {
            return $response;
        }

        return $this->successResponse(
            InventoryMovementResource::collection($this->inventoryMovementService->list($request)),
        );
    }
}
