<?php

namespace App\Models\Seo;

use Illuminate\Database\Eloquent\Model;

class SeoLocalOpportunity extends Model
{
    protected $table = 'seo_local_opportunities';

    protected $fillable = [
        'type', 'title', 'reason', 'priority', 'rank', 'payload', 'status', 'suggested_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'suggested_at' => 'datetime',
        ];
    }
}
