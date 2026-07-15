<?php

namespace App\Services\Property;

use App\DTOs\Property\CreatePropertyDTO;
use App\DTOs\Property\PropertySearchDTO;
use App\Models\Property;
use App\Models\PropertyFavorite;
use App\Models\User;
use App\Repositories\Contracts\PropertyRepositoryInterface;
use App\Services\Activity\ActivityLogger;
use App\Services\Subscription\SubscriptionAccessService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class PropertyService
{
    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly ActivityLogger $activityLogger,
        private readonly SubscriptionAccessService $subscriptionAccess,
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

        if (! $property->qr_token) {
            $property->update(['qr_token' => bin2hex(random_bytes(16))]);
            $property->refresh();
        }

        return $property;
    }

    public function create(User $user, CreatePropertyDTO $dto): Property
    {
        $this->ensurePropertyLimit($user);

        if ($this->propertyRepository->findByCode($user->office_id, $dto->code)) {
            throw ValidationException::withMessages([
                'code' => ['این کد ملک قبلاً ثبت شده است.'],
            ]);
        }

        $data = array_merge($dto->toArray(), [
            'office_id' => $user->office_id,
            'created_by' => $user->id,
            'assigned_to' => $dto->assignedTo ?? $user->id,
            'published_at' => now(),
            'qr_token' => bin2hex(random_bytes(16)),
        ]);

        $property = $this->propertyRepository->create($data);

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

    public function uploadMedia(User $user, int $id, $file, bool $isCover = false): \App\Models\PropertyMedia
    {
        $property = $this->find($user, $id);

        if (! $this->canEdit($user, $property)) {
            throw ValidationException::withMessages(['property' => ['مجاز به ویرایش نیستید.']]);
        }

        $path = $file->store("properties/{$property->id}", 'public');

        if ($isCover) {
            $property->media()->update(['is_cover' => false]);
        }

        return $property->media()->create([
            'type' => \App\Enums\MediaType::Image,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'sort_order' => $property->media()->count(),
            'is_cover' => $isCover || $property->media()->count() === 0,
        ]);
    }

    public function deleteMedia(User $user, int $id, int $mediaId): void
    {
        $property = $this->find($user, $id);

        if (! $this->canEdit($user, $property)) {
            throw ValidationException::withMessages(['property' => ['مجاز به ویرایش نیستید.']]);
        }

        $media = $property->media()->findOrFail($mediaId);

        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($media->path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($media->path);
        }

        $wasCover = $media->is_cover;
        $media->delete();

        if ($wasCover) {
            $next = $property->media()->first();
            if ($next) {
                $next->update(['is_cover' => true]);
            }
        }
    }

    public function setCoverMedia(User $user, int $id, int $mediaId): void
    {
        $property = $this->find($user, $id);

        if (! $this->canEdit($user, $property)) {
            throw ValidationException::withMessages(['property' => ['مجاز به ویرایش نیستید.']]);
        }

        $property->media()->update(['is_cover' => false]);
        $property->media()->where('id', $mediaId)->update(['is_cover' => true]);
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

        if (! $this->subscriptionAccess->userHasAccess($user)) {
            throw ValidationException::withMessages([
                'subscription' => ['دوره آزمایشی یا اشتراک شما به پایان رسیده است.'],
            ]);
        }

        $plan = $office?->plan ?? $office?->subscription?->plan;
        $maxProperties = $plan?->max_properties ?? 100;
        $currentCount = $this->propertyRepository->countByOffice($user->office_id);

        if ($currentCount >= $maxProperties) {
            throw ValidationException::withMessages([
                'subscription' => ['به حداکثر تعداد املاک مجاز رسیده‌اید. لطفاً اشتراک خود را ارتقا دهید.'],
            ]);
        }
    }
}
