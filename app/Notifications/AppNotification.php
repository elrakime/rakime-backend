<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AppNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array{en?: string, fr?: string, ar?: string}|string  $title
     * @param  array{en?: string, fr?: string, ar?: string}|string  $content
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly string $type,
        public readonly array|string $title,
        public readonly array|string $content,
        public readonly array $data = [],
        public readonly ?string $relatedType = null,
        public readonly ?int $relatedId = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type'         => $this->type,
            'title'        => $this->title,
            'content'      => $this->content,
            'data'         => $this->data,
            'related_type' => $this->relatedType,
            'related_id'   => $this->relatedId,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'type'    => $this->type,
            'title'   => $this->resolveLocalized($this->title),
            'content' => $this->resolveLocalized($this->content),
            'data'    => array_merge($this->data, [
                'type'         => $this->type,
                'related_type' => $this->relatedType,
                'related_id'   => $this->relatedId,
            ]),
        ];
    }

    /**
     * Resolve a value that may be a per-locale map into the current locale's string.
     *
     * @param  array{en?: string, fr?: string, ar?: string}|string  $value
     */
    private function resolveLocalized(array|string $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        $locale = app()->getLocale();

        return $value[$locale] ?? $value['en'] ?? reset($value) ?? '';
    }
}
