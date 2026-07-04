<?php

namespace App\DTOs\Property;

readonly class PropertySearchDTO
{
    public function __construct(
        public ?string $query = null,
        public ?string $type = null,
        public ?string $status = null,
        public ?string $permission = null,
        public ?int $minPrice = null,
        public ?int $maxPrice = null,
        public ?float $minArea = null,
        public ?float $maxArea = null,
        public ?int $rooms = null,
        public ?string $city = null,
        public ?string $district = null,
        public ?bool $hasParking = null,
        public ?bool $hasElevator = null,
        public ?bool $expired = null,
        public ?bool $favoritesOnly = null,
        public string $sortBy = 'created_at',
        public string $sortDir = 'desc',
        public int $perPage = 20,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            query: $data['q'] ?? $data['query'] ?? null,
            type: $data['type'] ?? null,
            status: $data['status'] ?? null,
            permission: $data['permission'] ?? null,
            minPrice: isset($data['min_price']) ? (int) $data['min_price'] : null,
            maxPrice: isset($data['max_price']) ? (int) $data['max_price'] : null,
            minArea: isset($data['min_area']) ? (float) $data['min_area'] : null,
            maxArea: isset($data['max_area']) ? (float) $data['max_area'] : null,
            rooms: isset($data['rooms']) ? (int) $data['rooms'] : null,
            city: $data['city'] ?? null,
            district: $data['district'] ?? null,
            hasParking: isset($data['has_parking']) ? filter_var($data['has_parking'], FILTER_VALIDATE_BOOLEAN) : null,
            hasElevator: isset($data['has_elevator']) ? filter_var($data['has_elevator'], FILTER_VALIDATE_BOOLEAN) : null,
            expired: isset($data['expired']) ? filter_var($data['expired'], FILTER_VALIDATE_BOOLEAN) : null,
            favoritesOnly: isset($data['favorites_only']) ? filter_var($data['favorites_only'], FILTER_VALIDATE_BOOLEAN) : null,
            sortBy: $data['sort_by'] ?? 'created_at',
            sortDir: $data['sort_dir'] ?? 'desc',
            perPage: min((int) ($data['per_page'] ?? 20), 100),
        );
    }
}
