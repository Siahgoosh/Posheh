<?php

namespace App\Imports;

use App\Models\Property;
use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PropertiesImport implements ToModel, WithHeadingRow
{
    private int $count = 0;

    public function __construct(private readonly User $user) {}

    public function model(array $row): ?Property
    {
        if (empty($row['code'])) {
            return null;
        }

        $this->count++;

        return Property::updateOrCreate(
            ['office_id' => $this->user->office_id, 'code' => (string) $row['code']],
            [
                'created_by' => $this->user->id,
                'assigned_to' => $this->user->id,
                'type' => $row['type'] ?? 'sale',
                'status' => $row['status'] ?? 'active',
                'price' => $row['price'] ?? null,
                'deposit' => $row['deposit'] ?? null,
                'rent' => $row['rent'] ?? null,
                'area' => $row['area'] ?? null,
                'rooms' => $row['rooms'] ?? null,
                'city' => $row['city'] ?? null,
                'district' => $row['district'] ?? null,
                'address' => $row['address'] ?? null,
                'owner_name' => $row['owner_name'] ?? null,
                'owner_mobile' => $row['owner_mobile'] ?? null,
                'description' => $row['description'] ?? null,
                'published_at' => now(),
            ]
        );
    }

    public function getRowCount(): int
    {
        return $this->count;
    }
}
