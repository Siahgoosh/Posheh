<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VirtualTourLead extends Model
{
    protected $fillable = [
        'virtual_tour_id',
        'name',
        'mobile',
        'message',
        'source',
    ];

    public function tour(): BelongsTo
    {
        return $this->belongsTo(VirtualTour::class, 'virtual_tour_id');
    }
}
