<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ProfileService
{
    public function update(User $user, array $data): User
    {
        $validated = validator($data, [
            'name' => ['sometimes', 'string', 'max:100'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'username' => ['sometimes', 'string', 'min:3', 'max:50', 'regex:/^[a-zA-Z0-9_]+$/', 'unique:users,username,'.$user->id],
            'mobile' => ['sometimes', 'string', 'regex:/^09\d{9}$/', 'unique:users,mobile,'.$user->id],
        ])->validate();

        $user->update($validated);

        return $user->fresh(['office.plan', 'office.subscription.plan']);
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (! $user->password || ! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['رمز عبور فعلی اشتباه است.'],
            ]);
        }

        validator(['password' => $newPassword], [
            'password' => ['required', 'string', Password::min(8)],
        ])->validate();

        $user->update(['password' => $newPassword]);
        $user->tokens()->where('id', '!=', $user->currentAccessToken()?->id)->delete();
    }
}
