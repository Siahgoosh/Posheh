<?php

namespace App\Services\Owner;

use App\Models\Owner;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OwnerService
{
    public function list(User $user, ?string $q = null): LengthAwarePaginator
    {
        return Owner::where('office_id', $user->office_id)
            ->withCount('properties')
            ->when($q, fn ($query) => $query->where(function ($q2) use ($q) {
                $q2->where('name', 'like', "%{$q}%")
                    ->orWhere('mobile', 'like', "%{$q}%")
                    ->orWhere('national_id', 'like', "%{$q}%");
            }))
            ->latest()
            ->paginate(20);
    }

    public function find(User $user, int $id): Owner
    {
        $owner = Owner::where('office_id', $user->office_id)
            ->with(['properties' => fn ($q) => $q->latest()->limit(20)])
            ->find($id);

        if (! $owner) {
            throw ValidationException::withMessages(['owner' => ['مالک یافت نشد.']]);
        }

        return $owner;
    }

    public function create(User $user, array $data): Owner
    {
        return Owner::create([
            ...$data,
            'office_id' => $user->office_id,
            'created_by' => $user->id,
            'portal_token' => Str::random(48),
        ]);
    }

    public function update(User $user, int $id, array $data): Owner
    {
        $owner = $this->find($user, $id);
        $owner->update($data);

        return $owner->fresh()->loadCount('properties');
    }

    public function delete(User $user, int $id): void
    {
        $owner = $this->find($user, $id);
        $owner->properties()->update(['owner_id' => null]);
        $owner->delete();
    }

    public function ensurePortalToken(User $user, int $id): Owner
    {
        $owner = $this->find($user, $id);

        if (! $owner->portal_token) {
            $owner->update(['portal_token' => Str::random(48)]);
            $owner = $owner->fresh();
        }

        return $owner;
    }
}
