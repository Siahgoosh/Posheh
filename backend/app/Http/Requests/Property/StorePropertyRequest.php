<?php

namespace App\Http\Requests\Property;

use App\Enums\PropertyCategory;
use App\Enums\PropertyPermission;
use App\Enums\PropertyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'type' => ['required', 'string', Rule::enum(PropertyType::class)],
            'property_category' => ['nullable', 'string', Rule::enum(PropertyCategory::class)],
            'permission' => ['nullable', 'string', Rule::enum(PropertyPermission::class)],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'owner_mobile' => ['nullable', 'string', 'max:20'],
            'price' => ['nullable', 'integer', 'min:0'],
            'deposit' => ['nullable', 'integer', 'min:0'],
            'rent' => ['nullable', 'integer', 'min:0'],
            'area' => ['nullable', 'numeric', 'min:0'],
            'rooms' => ['nullable', 'integer', 'min:0'],
            'building_age' => ['nullable', 'integer', 'min:0'],
            'floor' => ['nullable', 'integer'],
            'total_floors' => ['nullable', 'integer', 'min:0'],
            'has_parking' => ['nullable', 'boolean'],
            'has_elevator' => ['nullable', 'boolean'],
            'has_storage' => ['nullable', 'boolean'],
            'province' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'neighborhood' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:5000'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:50'],
            'expires_at' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
