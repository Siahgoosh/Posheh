<?php

namespace App\Repositories\Contracts;

use App\DTOs\Property\PropertySearchDTO;
use App\Models\Property;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PropertyRepositoryInterface
{
    public function findById(int $id): ?Property;

    public function findByCode(int $officeId, string $code): ?Property;

    public function search(User $user, PropertySearchDTO $dto): LengthAwarePaginator;

    public function getSimilar(Property $property, int $limit = 5): Collection;

    public function create(array $data): Property;

    public function update(Property $property, array $data): Property;

    public function delete(Property $property): bool;

    public function countByOffice(int $officeId): int;
}
