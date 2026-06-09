<?php

namespace App\Http\Controllers\Web;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\User\StoreUserRequest;
use App\Http\Requests\Web\User\UpdateUserRequest;
use App\Http\Resources\Web\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private readonly UserService $userService) {}

    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_USERS->value)) {
            return $response;
        }

        return $this->successResponse(UserResource::collection($this->userService->list($request)));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::CREATE_USERS->value)) {
            return $response;
        }

        try {
            $user = $this->userService->create($this->validateRequest($request));

            return $this->successResponse(new UserResource($user));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function show(User $user): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::VIEW_USERS->value)) {
            return $response;
        }

        try {
            return $this->successResponse(new UserResource($this->userService->show($user)));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::UPDATE_USERS->value)) {
            return $response;
        }

        try {
            $user = $this->userService->update($user, $this->validateRequest($request));

            return $this->successResponse(new UserResource($user));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }

    public function destroy(User $user): JsonResponse
    {
        if ($response = $this->authorizePermission(Permission::DELETE_USERS->value)) {
            return $response;
        }

        try {
            $this->userService->delete($user);

            return $this->successResponse(message: __('app.deleted'));
        } catch (\Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), statusCode: $e->getCode() ?? 400);
        }
    }
}
