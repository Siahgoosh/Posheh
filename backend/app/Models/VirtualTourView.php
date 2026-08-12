<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VirtualTourView extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'virtual_tour_id',
        'session_id',
        'ip',
        'user_agent',
        'referrer',
        'device_type',
        'screen_width',
        'screen_height',
        'duration_seconds',
        'viewed_at',
    ];

    protected function casts(): array
    {
        return ['viewed_at' => 'datetime'];
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(VirtualTour::class, 'virtual_tour_id');
    }
}
