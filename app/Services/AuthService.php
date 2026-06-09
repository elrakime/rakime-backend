<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * Validate credentials and return the user with roles, permissions, and branches.
     *
     * @throws \Exception
     */
    public function getUser(array $credentials): User
    {
        /** @var User|null $user */
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw new \Exception(__('auth.failed'), 422);
        }

        if (! $user->is_active) {
            throw new \Exception(__('auth.inactive'), 422);
        }

        return $user->loadMissing(['roles', 'permissions', 'branches']);
    }

    /**
     * Update the user's profile information.
     */
    public function updateProfile(User $user, array $data): User
    {
        $user->update($data);

        return $user->loadMissing(['roles', 'permissions', 'branches']);
    }

    /**
     * Change the user's password after verifying the current one.
     *
     * @throws \Exception
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw new \Exception(__('auth.password'), 422);
        }

        $user->update(['password' => $newPassword]);
    }
}
