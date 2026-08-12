<?php

namespace App\Models\Seo;

use Illuminate\Database\Eloquent\Model;

class SeoBusinessProfile extends Model
{
    protected $table = 'seo_business_profiles';

    protected $fillable = [
        'business_name', 'legal_name', 'brand', 'phone', 'email', 'support_email', 'website',
        'address_line', 'city', 'region', 'country', 'postal_code', 'working_hours', 'description',
        'services', 'social_profiles', 'logo_url', 'latitude', 'longitude', 'coords_verified',
        'nap_complete', 'status',
    ];

    protected function casts(): array
    {
        return [
            'working_hours' => 'array',
            'services' => 'array',
            'social_profiles' => 'array',
            'coords_verified' => 'boolean',
            'nap_complete' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function refreshNapComplete(): void
    {
        $this->nap_complete = filled($this->business_name)
            && filled($this->email)
            && filled($this->website);
        // Phone/address optional until verified — do not invent
    }
}
