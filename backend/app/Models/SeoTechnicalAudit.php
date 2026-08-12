<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoTechnicalAudit extends Model
{
    protected $fillable = [
        'scope',
        'status',
        'summary',
        'issues',
        'metrics',
        'ran_at',
    ];

    protected function casts(): array
    {
        return [
            'summary' => 'array',
            'issues' => 'array',
            'metrics' => 'array',
            'ran_at' => 'datetime',
        ];
    }
}
