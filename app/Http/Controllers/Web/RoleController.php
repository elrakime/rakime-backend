<?php

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Role\StoreRoleRequest;
use App\Http\Requests\Web\Role\UpdateRoleRequest;
use App\Http\Resources\Web\RoleResource;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(private readonly RoleService $roleService) {}

    public function index(): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_ROLES->value)) {
            return $response;
        }

        return $this->successResponse(RoleResource::collection($this->roleService->list()));
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::CREATE_ROLES->value)) {
            return $response;
        }

        try {
            $role = $this->roleService->create($this->validateRequest($request));

            return $this->successResponse(new RoleResource($role), statusCode: 201);
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function show(Role $role): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_ROLES->value)) {
            return $response;
        }

        try {
            return $this->successResponse(new RoleResource($this->roleService->show($role)));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::UPDATE_ROLES->value)) {
            return $response;
        }

        try {
            $role = $this->roleService->update($role, $this->validateRequest($request));

            return $this->successResponse(new RoleResource($role));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function destroy(Role $role): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::DELETE_ROLES->value)) {
            return $response;
        }

        try {
            $this->roleService->delete($role);

            return $this->successResponse(message: __('app.deleted'));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }
}
