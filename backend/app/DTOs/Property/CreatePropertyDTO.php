<?php

namespace App\DTOs\Property;

use App\Enums\PropertyCategory;
use App\Enums\PropertyPermission;
use App\Enums\PropertyType;

readonly class CreatePropertyDTO
{
    public function __construct(
        public string $code,
        public PropertyType $type,
        public ?PropertyCategory $propertyCategory = null,
        public PropertyPermission $permission,
        public ?string $ownerName = null,
        public ?string $ownerMobile = null,
        public ?int $price = null,
        public ?int $deposit = null,
        public ?int $rent = null,
        public ?float $area = null,
        public ?int $rooms = null,
        public ?int $buildingAge = null,
        public ?int $floor = null,
        public ?int $totalFloors = null,
        public bool $hasParking = false,
        public bool $hasElevator = false,
        public bool $hasStorage = false,
        public ?string $province = null,
        public ?string $city = null,
        public ?string $district = null,
        public ?string $neighborhood = null,
        public ?string $address = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?string $description = null,
        public ?array $features = null,
        public ?string $expiresAt = null,
        public ?int $assignedTo = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            code: $data['code'],
            type: PropertyType::from($data['type']),
            propertyCategory: isset($data['property_category']) ? PropertyCategory::from($data['property_category']) : null,
            permission: PropertyPermission::from($data['permission'] ?? 'office'),
            ownerName: $data['owner_name'] ?? null,
            ownerMobile: $data['owner_mobile'] ?? null,
            price: isset($data['price']) ? (int) $data['price'] : null,
            deposit: isset($data['deposit']) ? (int) $data['deposit'] : null,
            rent: isset($data['rent']) ? (int) $data['rent'] : null,
            area: isset($data['area']) ? (float) $data['area'] : null,
            rooms: $data['rooms'] ?? null,
            buildingAge: $data['building_age'] ?? null,
            floor: $data['floor'] ?? null,
            totalFloors: $data['total_floors'] ?? null,
            hasParking: (bool) ($data['has_parking'] ?? false),
            hasElevator: (bool) ($data['has_elevator'] ?? false),
            hasStorage: (bool) ($data['has_storage'] ?? false),
            province: $data['province'] ?? null,
            city: $data['city'] ?? null,
            district: $data['district'] ?? null,
            neighborhood: $data['neighborhood'] ?? null,
            address: $data['address'] ?? null,
            latitude: isset($data['latitude']) ? (float) $data['latitude'] : null,
            longitude: isset($data['longitude']) ? (float) $data['longitude'] : null,
            description: $data['description'] ?? null,
            features: $data['features'] ?? null,
            expiresAt: $data['expires_at'] ?? null,
            assignedTo: $data['assigned_to'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'code' => $this->code,
            'type' => $this->type->value,
            'property_category' => $this->propertyCategory?->value,
            'permission' => $this->permission->value,
            'owner_name' => $this->ownerName,
            'owner_mobile' => $this->ownerMobile,
            'price' => $this->price,
            'deposit' => $this->deposit,
            'rent' => $this->rent,
            'area' => $this->area,
            'rooms' => $this->rooms,
            'building_age' => $this->buildingAge,
            'floor' => $this->floor,
            'total_floors' => $this->totalFloors,
            'has_parking' => $this->hasParking,
            'has_elevator' => $this->hasElevator,
            'has_storage' => $this->hasStorage,
            'province' => $this->province,
            'city' => $this->city,
            'district' => $this->district,
            'neighborhood' => $this->neighborhood,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'description' => $this->description,
            'features' => $this->features,
            'expires_at' => $this->expiresAt,
            'assigned_to' => $this->assignedTo,
        ], fn ($v) => $v !== null);
    }
}
