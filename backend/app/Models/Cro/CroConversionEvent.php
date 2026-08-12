<?php

namespace App\Models\Cro;

use Illuminate\Database\Eloquent\Model;

class CroConversionEvent extends Model
{
    public $timestamps = false;

    protected $table = 'cro_conversion_events';

    protected $fillable = [
        'event_type', 'path', 'article_slug', 'cro_cta_id', 'cro_lead_id',
        'visitor_hash', 'session_id', 'meta', 'created_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];
}
