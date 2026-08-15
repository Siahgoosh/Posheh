<?php

namespace App\Services\Owner;

use App\Models\Owner;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

    /**
     * Match owner's properties against office customers (file ↔ customer fit).
     */
    public function matchCustomers(User $user, int $ownerId, int $limit = 20): array
    {
        $owner = $this->find($user, $ownerId);
        $matcher = app(\App\Services\Crm\PropertyMatchingService::class);
        $properties = $owner->properties()->where('status', 'active')->limit(30)->get();

        $byProperty = [];
        $customerBest = [];

        foreach ($properties as $property) {
            $rev = $matcher->reverseMatchForProperty($user, $property, $limit);
            $byProperty[] = [
                'property' => [
                    'id' => $property->id,
                    'code' => $property->code,
                    'type' => $property->type,
                    'city' => $property->city,
                    'price' => $property->price,
                    'rent' => $property->rent,
                ],
                'total' => $rev['total'],
                'summary' => $rev['summary'],
                'customers' => $rev['customers'],
            ];

            foreach ($rev['customers'] as $row) {
                $cid = $row['customer']['id'];
                if (! isset($customerBest[$cid]) || $row['score'] > $customerBest[$cid]['score']) {
                    $customerBest[$cid] = [
                        'customer' => $row['customer'],
                        'score' => $row['score'],
                        'band' => $row['band'],
                        'match_type' => $row['match_type'],
                        'property_id' => $property->id,
                        'property_code' => $property->code,
                        'checks' => $row['checks'],
                    ];
                }
            }
        }

        $topCustomers = collect($customerBest)->sortByDesc('score')->take($limit)->values()->all();

        return [
            'owner_id' => $owner->id,
            'owner_name' => $owner->name,
            'properties_count' => $properties->count(),
            'matched_customers_count' => count($topCustomers),
            'top_customers' => $topCustomers,
            'by_property' => $byProperty,
        ];
    }

    public function create(User $user, array $data): Owner
    {
        return Owner::create([
            ...$data,
            'office_id' => $user->office_id,
            'created_by' => $user->id,
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
}
