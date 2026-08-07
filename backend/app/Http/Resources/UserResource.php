<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'mobile' => $this->mobile,
            'email' => $this->email,
            'username' => $this->username,
            'role' => $this->role?->value,
            'role_label' => $this->role?->label(),
            'avatar_url' => $this->avatar_path ? url('storage/'.$this->avatar_path) : null,
            'is_active' => $this->is_active,
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'office' => $this->when(
                $this->relationLoaded('office') && $this->office !== null,
                fn () => new OfficeResource($this->office),
            ),
        ];
    }
}
