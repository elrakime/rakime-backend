<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;

class FcmChannel
{
    /**
     * Send the given notification to the notifiable's device tokens via FCM.
     *
     * @param  mixed  $notifiable
     */
    public function send($notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toFcm')) {
            return;
        }

        $payload = $notification->toFcm($notifiable);

        $tokens = $notifiable->deviceTokens()->pluck('token');

        if ($tokens->isEmpty()) {
            return;
        }

        // The Firebase SDK is optional at runtime: if it isn't installed or
        // configured, skip sending rather than breaking the request/queue job.
        if (! class_exists(\Kreait\Laravel\Firebase\Facades\Firebase::class)) {
            return;
        }

        $messaging = \Kreait\Laravel\Firebase\Facades\Firebase::messaging();

        $message = \Kreait\Firebase\Messaging\CloudMessage::new()
            ->withNotification([
                'title' => $payload['title'],
                'body'  => $payload['content'],
            ])
            ->withData($payload['data'] ?? []);

        // FCM v1 supports up to 500 tokens per multicast message.
        foreach ($tokens->chunk(500) as $chunk) {
            $multicast = $messaging->sendMulticast($message, $chunk->values()->all());
            // Report failures (e.g. unregistered tokens) without throwing.
            if ($multicast->failures()->count() > 0) {
                // Optionally log here.
            }
        }
    }
}
