<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;

class PropertyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'title' => $this->title,
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'building_type' => $this->building_type,
            'deed_type' => $this->deed_type,
            'direction' => $this->direction,
            'permission' => $this->permission?->value,
            'permission_label' => $this->permission?->label(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'owner_name' => $this->owner_name,
            'owner_mobile' => $this->owner_mobile,
            'owner_contact_id' => $this->owner_contact_id,
            'price' => $this->price,
            'price_per_meter' => $this->price_per_meter,
            'deposit' => $this->deposit,
            'rent' => $this->rent,
            'is_negotiable' => $this->is_negotiable,
            'commission_percent' => $this->commission_percent,
            'source' => $this->source,
            'area' => $this->area,
            'land_area' => $this->land_area,
            'rooms' => $this->rooms,
            'building_age' => $this->building_age,
            'renovation_status' => $this->renovation_status,
            'floor' => $this->floor,
            'total_floors' => $this->total_floors,
            'units_per_floor' => $this->units_per_floor,
            'has_parking' => $this->has_parking,
            'has_elevator' => $this->has_elevator,
            'has_storage' => $this->has_storage,
            'heating_type' => $this->heating_type,
            'cooling_type' => $this->cooling_type,
            'province' => $this->province,
            'city' => $this->city,
            'district' => $this->district,
            'neighborhood' => $this->neighborhood,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'description' => $this->description,
            'internal_notes' => $this->when($request->user()?->canManageOffice(), $this->internal_notes),
            'features' => $this->features,
            'amenities' => $this->amenities,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'expires_at_jalali' => $this->expires_at
                ? Jalalian::fromDateTime($this->expires_at)->format('Y/m/d')
                : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'created_at_jalali' => $this->created_at
                ? Jalalian::fromDateTime($this->created_at)->format('Y/m/d H:i')
                : null,
            'creator' => new UserResource($this->whenLoaded('creator')),
            'assignee' => new UserResource($this->whenLoaded('assignee')),
            'media' => PropertyMediaResource::collection($this->whenLoaded('media')),
            'cover_image' => $this->whenLoaded('media', function () {
                $cover = $this->coverImage();

                return $cover ? new PropertyMediaResource($cover) : null;
            }),
            'is_favorite' => $this->when(
                isset($this->is_favorite),
                (bool) ($this->is_favorite ?? false)
            ),
        ];
    }
}
