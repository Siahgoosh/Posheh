<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VirtualTourAnalyticsEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'virtual_tour_id',
        'session_id',
        'scene_id',
        'hotspot_id',
        'event_type',
        'position_x',
        'position_y',
        'meta',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'position_x' => 'float',
            'position_y' => 'float',
            'created_at' => 'datetime',
        ];
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(VirtualTour::class, 'virtual_tour_id');
    }

    public function scene(): BelongsTo
    {
        return $this->belongsTo(VirtualTourScene::class, 'scene_id');
    }
}
