<?php

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;

class PermissionController extends Controller
{
    public function __construct(private readonly PermissionService $permissionService) {}

    public function index(): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_PERMISSIONS->value)) {
            return $response;
        }

        return $this->successResponse($this->permissionService->list());
    }
}
