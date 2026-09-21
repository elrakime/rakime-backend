<?php

declare(strict_types=1);

use App\Enums\NotificationType;
use App\Enums\Role;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role as SpatieRole;

uses(RefreshDatabase::class);

function createRoles(): void
{
    foreach (Role::cases() as $role) {
        SpatieRole::firstOrCreate(['name' => $role->value]);
    }
}

function makeUser(string $role): User
{
    createRoles();

    return User::create([
        'name'     => 'Test ' . $role,
        'email'    => $role . rand(1000, 9999) . '@example.com',
        'password' => 'password',
        'is_active' => true,
    ])->assignRole($role);
}

test('registering a device token stores it uniquely per token', function () {
    $user = makeUser(Role::ADMIN->value);
    $service = app(NotificationService::class);

    $service->registerToken($user, 'token-abc', 'ios');
    $service->registerToken($user, 'token-abc', 'android');

    expect($user->deviceTokens()->count())->toBe(1);
    expect($user->deviceTokens()->first()->platform)->toBe('ios');
});

test('deleting a device token removes only the matching token', function () {
    $user = makeUser(Role::ADMIN->value);
    $service = app(NotificationService::class);

    $service->registerToken($user, 'token-abc');
    $service->registerToken($user, 'token-def');

    $service->deleteToken($user, 'token-abc');

    expect($user->deviceTokens()->pluck('token')->all())->toBe(['token-def']);
});

test('notifyAdmins sends a database notification to all admins only', function () {
    $admin1 = makeUser(Role::ADMIN->value);
    $admin2 = makeUser(Role::ADMIN->value);
    $manager = makeUser(Role::MANAGER->value);

    app(NotificationService::class)->notifyAdmins(
        type: NotificationType::RESTOCK_CREATED,
        title: ['en' => 'Restock', 'fr' => 'Réappro', 'ar' => 'إعادة'],
        content: ['en' => 'New restock', 'fr' => 'Nouveau', 'ar' => 'جديد'],
    );

    expect($admin1->notifications()->count())->toBe(1);
    expect($admin2->notifications()->count())->toBe(1);
    expect($manager->notifications()->count())->toBe(0);
});

test('notifyBranchUsers sends to managers and employees of the branch, excluding the actor', function () {
    $wilaya = \App\Models\Wilaya::create(['name' => 'Alger']);
    $branch = \App\Models\Branch::create(['wilaya_id' => $wilaya->id, 'name' => 'Branch', 'code' => 'B']);
    $branchId = $branch->id;
    $manager = makeUser(Role::MANAGER->value);
    $employee = makeUser(Role::EMPLOYEE->value);
    $otherManager = makeUser(Role::MANAGER->value);
    $admin = makeUser(Role::ADMIN->value);

    // Attach manager + employee to the branch.
    \DB::table('user_branches')->insert([
        ['user_id' => $manager->id, 'branch_id' => $branchId],
        ['user_id' => $employee->id, 'branch_id' => $branchId],
    ]);

    auth()->login($manager);

    app(NotificationService::class)->notifyBranchUsers(
        branchIds: $branchId,
        type: NotificationType::PURCHASE_RECEIVED,
        title: ['en' => 'Received', 'fr' => 'Reçu', 'ar' => 'تم'],
        content: ['en' => 'done', 'fr' => 'fait', 'ar' => 'تم'],
        except: $manager,
    );

    // Manager is the actor -> excluded. Employee gets it. Other manager/admin not in branch.
    expect($manager->notifications()->count())->toBe(0);
    expect($employee->notifications()->count())->toBe(1);
    expect($otherManager->notifications()->count())->toBe(0);
    expect($admin->notifications()->count())->toBe(0);
});

test('markAsRead marks only the given notification', function () {
    $user = makeUser(Role::ADMIN->value);

    $user->notify(new AppNotification(
        type: NotificationType::RESTOCK_CREATED->value,
        title: ['en' => 'A'],
        content: ['en' => 'B'],
    ));

    $notification = $user->notifications()->first();

    app(NotificationService::class)->markAsRead($user, $notification->id);

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('unreadCount returns the number of unread notifications', function () {
    $user = makeUser(Role::ADMIN->value);

    $user->notify(new AppNotification(
        type: NotificationType::RESTOCK_CREATED->value,
        title: ['en' => 'A'],
        content: ['en' => 'B'],
    ));
    $user->notify(new AppNotification(
        type: NotificationType::PURCHASE_CREATED->value,
        title: ['en' => 'A'],
        content: ['en' => 'B'],
    ));

    $first = $user->notifications()->first();
    app(NotificationService::class)->markAsRead($user, $first->id);

    expect(app(NotificationService::class)->unreadCount($user))->toBe(1);
});

test('AppNotification toFcm resolves current locale', function () {
    \Illuminate\Support\Facades\App::setLocale('fr');

    $notification = new AppNotification(
        type: NotificationType::PURCHASE_RECEIVED->value,
        title: ['en' => 'Purchase received', 'fr' => 'Achat reçu', 'ar' => 'تم الاستلام'],
        content: ['en' => 'Done', 'fr' => 'Fait', 'ar' => 'تم'],
        data: ['amount' => 100],
        relatedType: 'App\\Models\\Purchase',
        relatedId: 42,
    );

    $payload = $notification->toFcm(new User());

    expect($payload['title'])->toBe('Achat reçu');
    expect($payload['content'])->toBe('Fait');
    expect($payload['data']['related_type'])->toBe('App\\Models\\Purchase');
});
