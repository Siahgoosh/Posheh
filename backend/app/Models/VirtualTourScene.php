<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VirtualTourScene extends Model
{
    protected $fillable = [
        'virtual_tour_id',
        'name',
        'scene_type',
        'status',
        'is_default',
        'is_visible',
        'panorama_path',
        'thumbnail_path',
        'image_variants',
        'metadata',
        'panorama_width',
        'panorama_height',
        'file_size',
        'default_yaw',
        'default_pitch',
        'default_fov',
        'background_music',
        'ambient_sound',
        'transition_effect',
        'scene_settings',
        'sort_order',
        'floor_plan_x',
        'floor_plan_y',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_visible' => 'boolean',
            'default_yaw' => 'float',
            'default_pitch' => 'float',
            'default_fov' => 'integer',
            'scene_settings' => 'array',
            'image_variants' => 'array',
            'metadata' => 'array',
            'floor_plan_x' => 'float',
            'floor_plan_y' => 'float',
            'panorama_width' => 'integer',
            'panorama_height' => 'integer',
            'file_size' => 'integer',
        ];
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(VirtualTour::class, 'virtual_tour_id');
    }

    public function hotspots(): HasMany
    {
        return $this->hasMany(VirtualTourHotspot::class, 'scene_id');
    }
}
