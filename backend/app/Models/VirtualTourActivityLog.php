<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VirtualTourActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'virtual_tour_id',
        'user_id',
        'action',
        'ip',
        'meta',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(VirtualTour::class, 'virtual_tour_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
