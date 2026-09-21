<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Notification;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Notification\DeleteDeviceTokenRequest;
use App\Http\Requests\Api\Notification\RegisterDeviceTokenRequest;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;

class DeviceTokenController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function register(RegisterDeviceTokenRequest $request): JsonResponse
    {
        $validated = $this->validateRequest($request);

        $this->notificationService->registerToken(
            $request->user(),
            $validated['token'],
            $validated['platform'] ?? null,
        );

        return $this->successResponse(message: __('notifications.token_registered'));
    }

    public function destroy(DeleteDeviceTokenRequest $request): JsonResponse
    {
        $validated = $this->validateRequest($request);

        $this->notificationService->deleteToken($request->user(), $validated['token']);

        return $this->successResponse(message: __('notifications.token_deleted'));
    }
}
