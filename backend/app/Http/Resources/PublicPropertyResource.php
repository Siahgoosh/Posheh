<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Safe property payload for unauthenticated / public consumers.
 * Does not expose owner contacts, exact address, coordinates, or filing internals.
 */
class PublicPropertyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
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
            'area' => $this->area !== null ? (float) $this->area : null,
            'rooms' => $this->rooms,
            'building_age' => $this->building_age,
            'floor' => $this->floor,
            'total_floors' => $this->total_floors,
            'has_parking' => (bool) $this->has_parking,
            'has_elevator' => (bool) $this->has_elevator,
            'has_storage' => (bool) $this->has_storage,
            'province' => $this->province,
            'city' => $this->city,
            'district' => $this->district,
            'neighborhood' => $this->neighborhood,
            'description' => $this->description,
            'features' => $this->features,
            'media' => PropertyMediaResource::collection($this->whenLoaded('media')),
            'cover_image' => $this->whenLoaded('media', function () {
                $cover = $this->coverImage();

                return $cover ? new PropertyMediaResource($cover) : null;
            }),
        ];
    }
}
