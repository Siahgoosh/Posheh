<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VirtualTourHotspot extends Model
{
    protected $fillable = [
        'scene_id',
        'type',
        'target_scene_id',
        'yaw',
        'pitch',
        'title',
        'content',
        'link_url',
        'icon',
    ];

    protected function casts(): array
    {
        return [
            'yaw' => 'float',
            'pitch' => 'float',
        ];
    }

    public function scene(): BelongsTo
    {
        return $this->belongsTo(VirtualTourScene::class, 'scene_id');
    }

    public function targetScene(): BelongsTo
    {
        return $this->belongsTo(VirtualTourScene::class, 'target_scene_id');
    }
}
