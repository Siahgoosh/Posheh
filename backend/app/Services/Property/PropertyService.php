<?php

namespace App\Services\Property;

use App\DTOs\Property\CreatePropertyDTO;
use App\DTOs\Property\PropertySearchDTO;
use App\Models\Property;
use App\Models\PropertyFavorite;
use App\Models\User;
use App\Repositories\Contracts\PropertyRepositoryInterface;
use App\Services\Activity\ActivityLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class PropertyService
{
    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function list(User $user, PropertySearchDTO $dto): LengthAwarePaginator
    {
        return $this->propertyRepository->search($user, $dto);
    }

    public function find(User $user, int $id): Property
    {
        $property = $this->propertyRepository->findById($id);

        if (! $property || ! $this->canView($user, $property)) {
            throw ValidationException::withMessages([
                'property' => ['ملک مورد نظر یافت نشد.'],
            ]);
        }

        $property->is_favorite = PropertyFavorite::where('user_id', $user->id)
            ->where('property_id', $property->id)
            ->exists();

        return $property;
    }

    public function create(User $user, CreatePropertyDTO $dto): Property
    {
        return $this->createFromArray($user, $dto->toArray());
    }

    public function createFromArray(User $user, array $data): Property
    {
        $this->ensurePropertyLimit($user);

        if ($this->propertyRepository->findByCode($user->office_id, $data['code'])) {
            throw ValidationException::withMessages([
                'code' => ['این کد ملک قبلاً ثبت شده است.'],
            ]);
        }

        $payload = array_merge($data, [
            'office_id' => $user->office_id,
            'created_by' => $user->id,
            'assigned_to' => $data['assigned_to'] ?? $user->id,
            'published_at' => now(),
            'expires_at' => $data['expires_at'] ?? now()->addDays(30),
        ]);

        $property = $this->propertyRepository->create($payload);

        $this->activityLogger->log($user, 'property.created', $property, 'ملک جدید ثبت شد');

        return $property->load(['media', 'creator']);
    }

    public function update(User $user, int $id, array $data): Property
    {
        $property = $this->find($user, $id);

        if (! $this->canEdit($user, $property)) {
            throw ValidationException::withMessages([
                'property' => ['شما مجاز به ویرایش این ملک نیستید.'],
            ]);
        }

        $updated = $this->propertyRepository->update($property, $data);

        $this->activityLogger->log($user, 'property.updated', $updated, 'ملک ویرایش شد');

        return $updated;
    }

    public function delete(User $user, int $id): void
    {
        $property = $this->find($user, $id);

        if (! $this->canEdit($user, $property)) {
            throw ValidationException::withMessages([
                'property' => ['شما مجاز به حذف این ملک نیستید.'],
            ]);
        }

        $this->propertyRepository->delete($property);
        $this->activityLogger->log($user, 'property.deleted', $property, 'ملک حذف شد');
    }

    public function getSimilar(User $user, int $id): Collection
    {
        $property = $this->find($user, $id);

        return $this->propertyRepository->getSimilar($property);
    }

    public function toggleFavorite(User $user, int $id): array
    {
        $property = $this->find($user, $id);

        $favorite = PropertyFavorite::where('user_id', $user->id)
            ->where('property_id', $property->id)
            ->first();

        if ($favorite) {
            $favorite->delete();

            return ['is_favorite' => false];
        }

        PropertyFavorite::create([
            'user_id' => $user->id,
            'property_id' => $property->id,
        ]);

        return ['is_favorite' => true];
    }

    public function canView(User $user, Property $property): bool
    {
        if ($user->canManageOffice() && $user->office_id === $property->office_id) {
            return true;
        }

        if ($user->office_id !== $property->office_id) {
            return false;
        }

        return match ($property->permission->value) {
            'office', 'team' => true,
            'private' => $property->created_by === $user->id || $property->assigned_to === $user->id,
            'manager_only' => false,
            default => false,
        };
    }

    public function canEdit(User $user, Property $property): bool
    {
        if ($user->canManageOffice() && $user->office_id === $property->office_id) {
            return true;
        }

        return $property->created_by === $user->id || $property->assigned_to === $user->id;
    }

    private function ensurePropertyLimit(User $user): void
    {
        $office = $user->office;
        $subscription = $office?->subscription;

        if (! $subscription?->isActive()) {
            throw ValidationException::withMessages([
                'subscription' => ['اشتراک دفتر منقضی شده است.'],
            ]);
        }

        $maxProperties = $subscription->plan->max_properties;
        $currentCount = $this->propertyRepository->countByOffice($user->office_id);

        if ($currentCount >= $maxProperties) {
            throw ValidationException::withMessages([
                'subscription' => ['به حداکثر تعداد املاک مجاز رسیده‌اید. لطفاً اشتراک خود را ارتقا دهید.'],
            ]);
        }
    }
}
