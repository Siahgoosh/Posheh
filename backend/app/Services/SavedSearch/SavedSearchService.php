<?php

namespace App\Services\SavedSearch;

use App\Models\Property;
use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class SavedSearchService
{
    public function list(User $user)
    {
        return SavedSearch::where('office_id', $user->office_id)
            ->where('user_id', $user->id)
            ->latest()
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
        $search = SavedSearch::where('office_id', $user->office_id)
            ->where('user_id', $user->id)
            ->findOrFail($id);
        $search->delete();
    }

    public function run(User $user, int $id): array
    {
        $search = SavedSearch::where('office_id', $user->office_id)
            ->where('user_id', $user->id)
            ->findOrFail($id);

        $filters = $search->filters ?? [];
        $query = Property::where('office_id', $user->office_id);

        if (! empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($builder) use ($q) {
                $builder->where('code', 'like', "%{$q}%")
                    ->orWhere('address', 'like', "%{$q}%")
                    ->orWhere('city', 'like', "%{$q}%")
                    ->orWhere('district', 'like', "%{$q}%");
            });
        }

        foreach (['type', 'status', 'city', 'district', 'deal_type'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        if (! empty($filters['price_min'])) {
            $query->where('price', '>=', (int) $filters['price_min']);
        }

        if (! empty($filters['price_max'])) {
            $query->where('price', '<=', (int) $filters['price_max']);
        }

        $results = $query->latest()->limit(50)->get();

        return [
            'search' => $search,
            'count' => $results->count(),
            'results' => $results,
        ];
    }
}
