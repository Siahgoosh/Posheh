<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Model;

class BlogImageBatch extends Model
{
    protected $table = 'blog_image_batches';

    protected $fillable = [
        'name', 'status', 'batch_size', 'total', 'processed', 'success', 'failed', 'rejected',
        'estimated_cost_toman', 'actual_cost_toman', 'provider', 'model', 'resolution',
        'dry_run', 'filters', 'created_by', 'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'dry_run' => 'boolean',
            'filters' => 'array',
            'confirmed_at' => 'datetime',
        ];
    }
}
