<?php

namespace App\Services\SavedSearch;

use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class SavedSearchService
{
    public function list(User $user)
    {
        return SavedSearch::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();
    }

    public function create(User $user, array $data): SavedSearch
    {
        return SavedSearch::create([
            'user_id' => $user->id,
            'office_id' => $user->office_id,
            'name' => $data['name'],
            'filters' => $data['filters'] ?? [],
            'notify_on_match' => $data['notify_on_match'] ?? false,
        ]);
    }

    public function delete(User $user, int $id): void
    {
        $search = SavedSearch::where('user_id', $user->id)->find($id);

        if (! $search) {
            throw ValidationException::withMessages([
                'search' => ['جستجوی ذخیره‌شده یافت نشد.'],
            ]);
        }

        $search->delete();
    }
}
