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
        'panorama_path',
        'thumbnail_path',
        'default_yaw',
        'default_pitch',
        'sort_order',
        'floor_plan_x',
        'floor_plan_y',
    ];

    protected function casts(): array
    {
        return [
            'default_yaw' => 'float',
            'default_pitch' => 'float',
            'floor_plan_x' => 'float',
            'floor_plan_y' => 'float',
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
