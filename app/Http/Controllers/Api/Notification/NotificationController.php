<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Notification;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function index(Request $request): JsonResponse
    {
        $notifications = $this->notificationService->list($request, $request->user());

        return $this->successResponse(NotificationResource::collection($notifications));
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return $this->successResponse([
            'count' => $this->notificationService->unreadCount($request->user()),
        ]);
    }

    public function read(Request $request, string $notification): JsonResponse
    {
        $this->notificationService->markAsRead($request->user(), $notification);

        return $this->successResponse(message: __('notifications.marked_read'));
    }
}
