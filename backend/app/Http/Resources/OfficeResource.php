<?php

namespace App\Http\Resources;

use App\Services\Subscription\SubscriptionAccessService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfficeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $access = app(SubscriptionAccessService::class)->accessStatus($this->resource);

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'description' => $this->description,
            'logo_url' => $this->logo_path ? url('storage/'.$this->logo_path) : null,
            'panel_type' => $this->panel_type,
            'is_active' => $this->is_active,
            'is_verified' => $this->is_verified,
            'show_on_website' => $this->show_on_website,
            'trial_ends_at' => $this->trial_ends_at?->toIso8601String(),
            'has_access' => $access['has_access'],
            'on_trial' => $access['on_trial'],
            'trial_days_remaining' => $access['trial_days_remaining'],
            'trial_hours_remaining' => $access['trial_hours_remaining'],
            'trial_label' => $access['trial_label'],
            'subscription_expired' => $access['subscription_expired'],
            'plan' => $this->whenLoaded('plan', fn () => [
                'id' => $this->plan?->id,
                'slug' => $this->plan?->slug,
                'name' => $this->plan?->name,
                'panel_type' => $this->plan?->panel_type,
                'features' => $this->plan?->features ?? [],
            ]),
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
