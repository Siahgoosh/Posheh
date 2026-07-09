<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    public function findByMobile(string $mobile): ?User
    {
        $normalized = preg_replace('/\D/', '', $mobile);
        if (str_starts_with($normalized, '98')) {
            $normalized = '0'.substr($normalized, 2);
        }
        if ($normalized !== '' && ! str_starts_with($normalized, '0')) {
            $normalized = '0'.$normalized;
        }

        return User::query()
            ->whereIn('mobile', array_unique(array_filter([
                $mobile,
                $normalized,
                ltrim($normalized, '0'),
                '98'.ltrim($normalized, '0'),
            ])))
            ->first();
    }

    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user->fresh();
    }

    public function countByOffice(int $officeId): int
    {
        return User::where('office_id', $officeId)->where('is_active', true)->count();
    }
}
