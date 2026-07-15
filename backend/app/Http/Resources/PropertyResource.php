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
            'qr_token' => $this->qr_token,
            'qr_url' => $this->qr_token
                ? rtrim(config('app.frontend_url', config('app.url')), '/').'/p/'.$this->qr_token
                : null,
            'owner_id' => $this->owner_id,
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'property_category' => $this->property_category?->value,
            'property_category_label' => $this->property_category?->label(),
            'permission' => $this->permission?->value,
            'permission_label' => $this->permission?->label(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'owner_name' => $this->owner_name,
            'owner_mobile' => $this->owner_mobile,
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
            'quality_score' => $this->when(
                $this->relationLoaded('media') || $this->media,
                fn () => app(\App\Services\Property\PropertyShareService::class)->qualityScore($this->resource)
            ),
        ];
    }
}
