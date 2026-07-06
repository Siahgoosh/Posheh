<?php

namespace App\Repositories\Eloquent;

use App\DTOs\Property\PropertySearchDTO;
use App\Enums\PropertyStatus;
use App\Models\Property;
use App\Models\PropertyFavorite;
use App\Models\User;
use App\Repositories\Contracts\PropertyRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PropertyRepository implements PropertyRepositoryInterface
{
    public function findById(int $id): ?Property
    {
        return Property::with(['media', 'creator', 'assignee'])->find($id);
    }

    public function findByCode(int $officeId, string $code): ?Property
    {
        return Property::where('office_id', $officeId)->where('code', $code)->first();
    }

    public function search(User $user, PropertySearchDTO $dto): LengthAwarePaginator
    {
        $query = Property::query()
            ->with(['media', 'creator'])
            ->visibleTo($user);

        if ($dto->query) {
            $query->where(function ($q) use ($dto) {
                $q->where('code', 'like', "%{$dto->query}%")
                    ->orWhere('owner_name', 'like', "%{$dto->query}%")
                    ->orWhere('address', 'like', "%{$dto->query}%")
                    ->orWhere('description', 'like', "%{$dto->query}%")
                    ->orWhere('neighborhood', 'like', "%{$dto->query}%");
            });
        }

        if ($dto->type) {
            $query->where('type', $dto->type);
        }

        if ($dto->status) {
            $query->where('status', $dto->status);
        } elseif ($dto->expired === true) {
            $query->where('status', PropertyStatus::Expired);
        } elseif ($dto->expired === false) {
            $query->where('status', '!=', PropertyStatus::Expired);
        }

        if ($dto->minPrice) {
            $query->where('price', '>=', $dto->minPrice);
        }

        if ($dto->maxPrice) {
            $query->where('price', '<=', $dto->maxPrice);
        }

        if ($dto->minArea) {
            $query->where('area', '>=', $dto->minArea);
        }

        if ($dto->maxArea) {
            $query->where('area', '<=', $dto->maxArea);
        }

        if ($dto->rooms) {
            $query->where('rooms', $dto->rooms);
        }

        if ($dto->city) {
            $query->where('city', $dto->city);
        }

        if ($dto->district) {
            $query->where('district', $dto->district);
        }

        if ($dto->hasParking !== null) {
            $query->where('has_parking', $dto->hasParking);
        }

        if ($dto->hasElevator !== null) {
            $query->where('has_elevator', $dto->hasElevator);
        }

        if ($dto->favoritesOnly) {
            $favoriteIds = PropertyFavorite::where('user_id', $user->id)->pluck('property_id');
            $query->whereIn('id', $favoriteIds);
        }

        return $query->orderBy($dto->sortBy, $dto->sortDir)->paginate($dto->perPage);
    }

    public function getSimilar(Property $property, int $limit = 5): Collection
    {
        return Property::query()
            ->where('id', '!=', $property->id)
            ->where('office_id', $property->office_id)
            ->where('type', $property->type)
            ->where('status', PropertyStatus::Active)
            ->when($property->city, fn ($q) => $q->where('city', $property->city))
            ->when($property->rooms, fn ($q) => $q->where('rooms', $property->rooms))
            ->when($property->price, function ($q) use ($property) {
                $margin = $property->price * 0.2;
                $q->whereBetween('price', [$property->price - $margin, $property->price + $margin]);
            })
            ->with('media')
            ->limit($limit)
            ->get();
    }

    public function create(array $data): Property
    {
        return Property::create($data);
    }

    public function update(Property $property, array $data): Property
    {
        $property->update($data);

        return $property->fresh(['media', 'creator', 'assignee']);
    }

    public function delete(Property $property): bool
    {
        return (bool) $property->delete();
    }

    public function countByOffice(int $officeId): int
    {
        return Property::where('office_id', $officeId)->count();
    }
}
