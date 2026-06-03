<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Validate credentials and return the user with roles, permissions, and branches.
     *
     * @throws ValidationException
     */
    public function getUser(array $credentials): User
    {
        /** @var User|null $user */
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => [__('auth.inactive')],
            ]);
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
     * @throws ValidationException
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => [__('auth.password')],
            ]);
        }

        $user->update(['password' => $newPassword]);
    }
}
