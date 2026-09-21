<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NotificationType;
use App\Enums\Role;
use App\Models\DeviceToken;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class NotificationService
{
    /**
     * Send a notification to all admins.
     *
     * @param  array{en?: string, fr?: string, ar?: string}|string  $title
     * @param  array{en?: string, fr?: string, ar?: string}|string  $content
     * @param  array<string, mixed>  $data
     */
    public function notifyAdmins(
        NotificationType|string $type,
        array|string $title,
        array|string $content,
        array $data = [],
        ?Model $related = null,
    ): void {
        $users = User::role(Role::ADMIN->value)->get();

        $this->send($users, $type, $title, $content, $data, $related);
    }

    /**
     * Send a notification to employees/managers with access to the given branches.
     *
     * @param  int|array<int, int>  $branchIds
     * @param  array{en?: string, fr?: string, ar?: string}|string  $title
     * @param  array{en?: string, fr?: string, ar?: string}|string  $content
     * @param  array<string, mixed>  $data
     */
    public function notifyBranchUsers(
        int|array $branchIds,
        NotificationType|string $type,
        array|string $title,
        array|string $content,
        array $data = [],
        ?Model $related = null,
        ?User $except = null,
    ): void {
        $branchIds = array_filter((array) $branchIds);

        if ($branchIds === []) {
            return;
        }

        $users = User::query()
            ->whereHas('branches', fn ($q) => $q->whereIn('branch_id', $branchIds))
            ->role([Role::MANAGER->value, Role::EMPLOYEE->value])
            ->when($except !== null, fn ($q) => $q->whereKeyNot($except->getKey()))
            ->get();

        $this->send($users, $type, $title, $content, $data, $related);
    }

    /**
     * Dispatch a notification for a model action, applying the R1/R2 rules.
     *
     * R1: creation of a model needing admin action -> notify admins.
     * R2: admin decision/action -> notify branch staff (excluding the actor).
     *
     * @param  int|array<int, int>|null  $branchIds  Branch IDs relevant to the model; null for R1.
     * @param  array{en?: string, fr?: string, ar?: string}|string  $title
     * @param  array{en?: string, fr?: string, ar?: string}|string  $content
     * @param  array<string, mixed>  $data
     */
    public function notifyForModel(
        Model $model,
        NotificationType|string $type,
        array|string $title,
        array|string $content,
        array $data = [],
        int|array|null $branchIds = null,
    ): void {
        $related = $model;
        $actor = auth()->user();

        $data = array_merge($data, [
            'id' => $model->getKey(),
        ]);

        // R1: no branch context -> it's a request needing admin attention.
        if ($branchIds === null) {
            $this->notifyAdmins($type, $title, $content, $data, $related);

            return;
        }

        // R2: admin decision -> notify branch staff (excluding the actor).
        $this->notifyBranchUsers($branchIds, $type, $title, $content, $data, $related, $actor);
    }

    /**
     * Register a device token for a user (token is globally unique).
     */
    public function registerToken(User $user, string $token, ?string $platform = null): DeviceToken
    {
        return DeviceToken::query()->firstOrCreate(
            ['token' => $token],
            ['user_id' => $user->id, 'platform' => $platform],
        );
    }

    /**
     * Delete a device token for a user (if it belongs to them).
     */
    public function deleteToken(User $user, string $token): void
    {
        $user->deviceTokens()->where('token', $token)->delete();
    }

    /**
     * List the authenticated user's notifications (paginated).
     */
    public function list(Request $request, User $user): LengthAwarePaginator
    {
        return QueryBuilder::for($user->notifications()->getQuery(), $request)
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 15))
            ->appends($request->query());
    }

    /**
     * Count the authenticated user's unread notifications.
     */
    public function unreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    /**
     * Mark a notification as read (if it belongs to the user).
     */
    public function markAsRead(User $user, string $notificationId): void
    {
        $user->notifications()->whereKey($notificationId)->update(['read_at' => now()]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, User>  $users
     * @param  array{en?: string, fr?: string, ar?: string}|string  $title
     * @param  array{en?: string, fr?: string, ar?: string}|string  $content
     * @param  array<string, mixed>  $data
     */
    private function send(
        \Illuminate\Support\Collection $users,
        NotificationType|string $type,
        array|string $title,
        array|string $content,
        array $data,
        ?Model $related,
    ): void {
        $type = $type instanceof NotificationType ? $type->value : $type;

        foreach ($users as $user) {
            $user->notify(new AppNotification(
                type: $type,
                title: $title,
                content: $content,
                data: $data,
                relatedType: $related !== null ? $related::class : null,
                relatedId: $related !== null ? $related->getKey() : null,
            ));
        }
    }
}
