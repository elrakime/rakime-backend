<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;

/**
 * @mixin DatabaseNotification
 */
class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = $this->data ?? [];

        $title = $this->resolveLocalized($data['title'] ?? null);
        $content = $this->resolveLocalized($data['content'] ?? null);

        return [
            'id'           => $this->id,
            'type'         => $data['type'] ?? null,
            'title'        => $title,
            'content'      => $content,
            'data'         => $data['data'] ?? [],
            'related'      => isset($data['related_type'], $data['related_id'])
                ? ['type' => $data['related_type'], 'id' => $data['related_id']]
                : null,
            'read_at'      => $this->read_at,
            'created_at'   => $this->created_at,
        ];
    }

    /**
     * @param  array{en?: string, fr?: string, ar?: string}|string|null  $value
     */
    private function resolveLocalized(array|string|null $value): ?string
    {
        if ($value === null || is_string($value)) {
            return $value;
        }

        $locale = app()->getLocale();

        return $value[$locale] ?? $value['en'] ?? reset($value) ?? null;
    }
}
