<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Auth\ChangePasswordRequest;
use App\Http\Requests\Web\Auth\LoginRequest;
use App\Http\Requests\Web\Auth\UpdateProfileRequest;
use App\Http\Resources\Web\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $this->validateRequest($request);

        $user = $this->authService->getUser($validated);

        $token = $user->createToken('web')->plainTextToken;

        return $this->successResponse([
            'token' => $token,
            'user'  => new UserResource($user),
        ], __('auth.success'));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(message: __('auth.logged_out'));
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing(['roles', 'permissions', 'branches']);

        return $this->successResponse(new UserResource($user));
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $validated = $this->validateRequest($request);

        $user = $this->authService->updateProfile($request->user(), $validated);

        return $this->successResponse(new UserResource($user), __('auth.profile_updated'));
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $validated = $this->validateRequest($request);

        $this->authService->changePassword(
            $request->user(),
            $validated['current_password'],
            $validated['new_password'],
        );

        return $this->successResponse(message: __('auth.password_changed'));
    }
}
