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
        'label',
        'tooltip',
        'content',
        'link_url',
        'icon',
        'style',
        'action',
        'popup',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'yaw' => 'float',
            'pitch' => 'float',
            'style' => 'array',
            'action' => 'array',
            'popup' => 'array',
            'sort_order' => 'integer',
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
