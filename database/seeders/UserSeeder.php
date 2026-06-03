<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'              => 'Admin',
                'password'          => 'password',
                'is_active'         => true,
                'email_verified_at' => now(),
            ]
        );

        $admin->syncRoles([Role::ADMIN->value]);
    }
}
