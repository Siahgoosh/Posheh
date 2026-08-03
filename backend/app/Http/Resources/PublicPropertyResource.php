<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Public-facing property data — excludes owner PII and internal fields. */
class PublicPropertyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $filingData = $this->filing_data;
        if (is_array($filingData)) {
            unset($filingData['owner']);
        }

        return [
            'id' => $this->id,
            'code' => $this->code,
            'title' => $this->title,
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'property_category' => $this->property_category?->value,
            'property_category_label' => $this->property_category?->label(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'price' => $this->price,
            'deposit' => $this->deposit,
            'rent' => $this->rent,
            'area' => $this->area,
            'rooms' => $this->rooms,
            'building_age' => $this->building_age,
            'floor' => $this->floor,
            'total_floors' => $this->total_floors,
            'has_parking' => $this->has_parking,
            'has_elevator' => $this->has_elevator,
            'has_storage' => $this->has_storage,
            'province' => $this->province,
            'city' => $this->city,
            'district' => $this->district,
            'neighborhood' => $this->neighborhood,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'description' => $this->description,
            'features' => $this->features,
            'tags' => $this->tags,
            'filing_data' => $filingData,
            'document_status' => $this->document_status,
            'media' => PropertyMediaResource::collection($this->whenLoaded('media')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
