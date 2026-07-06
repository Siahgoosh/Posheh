<?php

namespace App\Http\Requests\Property;

use Illuminate\Foundation\Http\FormRequest;

class StorePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50'],
            'title' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string'],
            'building_type' => ['nullable', 'string', 'max:50'],
            'deed_type' => ['nullable', 'string', 'max:50'],
            'direction' => ['nullable', 'string', 'max:30'],
            'permission' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'owner_mobile' => ['nullable', 'string', 'max:15'],
            'owner_contact_id' => ['nullable', 'integer', 'exists:contacts,id'],
            'price' => ['nullable', 'integer', 'min:0'],
            'price_per_meter' => ['nullable', 'integer', 'min:0'],
            'deposit' => ['nullable', 'integer', 'min:0'],
            'rent' => ['nullable', 'integer', 'min:0'],
            'is_negotiable' => ['nullable', 'boolean'],
            'commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'source' => ['nullable', 'string', 'max:50'],
            'area' => ['nullable', 'numeric', 'min:0'],
            'land_area' => ['nullable', 'numeric', 'min:0'],
            'rooms' => ['nullable', 'integer', 'min:0', 'max:20'],
            'building_age' => ['nullable', 'integer', 'min:0'],
            'renovation_status' => ['nullable', 'string', 'max:50'],
            'floor' => ['nullable', 'integer'],
            'total_floors' => ['nullable', 'integer', 'min:0'],
            'units_per_floor' => ['nullable', 'integer', 'min:0'],
            'has_parking' => ['nullable', 'boolean'],
            'has_elevator' => ['nullable', 'boolean'],
            'has_storage' => ['nullable', 'boolean'],
            'heating_type' => ['nullable', 'string', 'max:50'],
            'cooling_type' => ['nullable', 'string', 'max:50'],
            'province' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'neighborhood' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'description' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'features' => ['nullable', 'array'],
            'amenities' => ['nullable', 'array'],
            'expires_at' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
