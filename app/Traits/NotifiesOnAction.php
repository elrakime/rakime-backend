<?php

declare(strict_types=1);

namespace App\Traits;

use App\Enums\NotificationType;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Model;

/**
 * Centralized notification dispatch for approval/action flows.
 *
 * R1: model creation (needs admin action) -> notify all admins.
 * R2: admin decision/action -> notify branch staff (excluding the actor).
 */
trait NotifiesOnAction
{
    protected function notifications(): NotificationService
    {
        return app(NotificationService::class);
    }

    /**
     * Notify admins that a model needing their action was created.
     */
    protected function notifyCreated(Model $model, NotificationType $type, array $data = []): void
    {
        $this->notifications()->notifyAdmins(
            type: $type,
            title: $this->title($type),
            content: $this->content($type, $model),
            data: $data,
            related: $model,
        );
    }

    /**
     * Notify branch staff that an action was performed on a model.
     *
     * @param  int|array<int, int>  $branchIds
     */
    protected function notifyAction(Model $model, NotificationType $type, int|array $branchIds, array $data = []): void
    {
        $this->notifications()->notifyBranchUsers(
            branchIds: $branchIds,
            type: $type,
            title: $this->title($type),
            content: $this->content($type, $model),
            data: $data,
            related: $model,
            except: auth()->user(),
        );
    }

    /**
     * @param  array{en?: string, fr?: string, ar?: string}|string  $value
     */
    private function localize(array|string $value): array|string
    {
        return $value;
    }

    /**
     * Build a localized title from the notification type.
     *
     * @return array{en: string, fr: string, ar: string}
     */
    private function title(NotificationType $type): array
    {
        return [
            'en' => __('enums.notification_type.' . $type->value, [], 'en'),
            'fr' => __('enums.notification_type.' . $type->value, [], 'fr'),
            'ar' => __('enums.notification_type.' . $type->value, [], 'ar'),
        ];
    }

    /**
     * Build a localized content string referencing the model.
     *
     * @return array{en: string, fr: string, ar: string}
     */
    private function content(NotificationType $type, Model $model): array
    {
        $reference = $model->getAttribute('reference') ?? ('#' . $model->getKey());

        $label = $this->modelLabel($model);

        return [
            'en' => __('notifications.action_content', ['label' => $label, 'reference' => $reference], 'en'),
            'fr' => __('notifications.action_content', ['label' => $label, 'reference' => $reference], 'fr'),
            'ar' => __('notifications.action_content', ['label' => $label, 'reference' => $reference], 'ar'),
        ];
    }

    private function modelLabel(Model $model): string
    {
        return str(class_basename($model))->snake(' ')->title()->toString();
    }
}
