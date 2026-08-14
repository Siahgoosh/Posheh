<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Model;

class ContentOpsReport extends Model
{
    protected $table = 'content_ops_reports';

    protected $fillable = [
        'type', 'period_start', 'period_end', 'payload',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'payload' => 'array',
        ];
    }
}
