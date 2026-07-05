<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfficeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'logo_url' => $this->logo_path ? url('storage/'.$this->logo_path) : null,
            'is_active' => $this->is_active,
            'trial_ends_at' => $this->trial_ends_at?->toIso8601String(),
            'subscription' => $this->whenLoaded('subscription', function () {
                return [
                    'status' => $this->subscription?->status,
                    'ends_at' => $this->subscription?->ends_at?->toIso8601String(),
                    'plan' => $this->subscription?->plan?->name,
                ];
            }),
        ];
    }
}
