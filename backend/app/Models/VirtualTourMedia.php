<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VirtualTourMedia extends Model
{
    protected $table = 'virtual_tour_media';

    protected $fillable = [
        'virtual_tour_id',
        'type',
        'path',
        'title',
        'sort_order',
    ];

    public function tour(): BelongsTo
    {
        return $this->belongsTo(VirtualTour::class, 'virtual_tour_id');
    }
}
