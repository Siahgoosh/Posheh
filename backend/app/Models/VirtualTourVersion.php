<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VirtualTourVersion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'virtual_tour_id',
        'created_by',
        'version_number',
        'label',
        'snapshot',
        'size_bytes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'version_number' => 'integer',
            'size_bytes' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(VirtualTour::class, 'virtual_tour_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
