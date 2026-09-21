<?php

declare(strict_types=1);

use App\Enums\NotificationType;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('AppNotification toDatabase stores localized title/content and related morph', function () {
    $notification = new AppNotification(
        type: NotificationType::PURCHASE_RECEIVED->value,
        title: ['en' => 'Purchase received', 'fr' => 'Achat reçu', 'ar' => 'تم الاستلام'],
        content: ['en' => 'Done', 'fr' => 'Fait', 'ar' => 'تم'],
        data: ['amount' => 100],
        relatedType: 'App\\Models\\Purchase',
        relatedId: 42,
    );

    $payload = $notification->toDatabase(new User());

    expect($payload['type'])->toBe('purchase_received');
    expect($payload['title'])->toBe(['en' => 'Purchase received', 'fr' => 'Achat reçu', 'ar' => 'تم الاستلام']);
    expect($payload['content'])->toBe(['en' => 'Done', 'fr' => 'Fait', 'ar' => 'تم']);
    expect($payload['data'])->toBe(['amount' => 100]);
    expect($payload['related_type'])->toBe('App\\Models\\Purchase');
    expect($payload['related_id'])->toBe(42);
});

test('AppNotification via returns database and fcm channels', function () {
    $notification = new AppNotification(
        type: NotificationType::PURCHASE_RECEIVED->value,
        title: ['en' => 'A'],
        content: ['en' => 'B'],
    );

    expect($notification->via(new User()))->toBe(['database', \App\Notifications\Channels\FcmChannel::class]);
});
