<?php

namespace App\Models\Seo;

use App\Models\BlogPost;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoContentExperiment extends Model
{
    protected $table = 'seo_content_experiments';

    protected $fillable = [
        'blog_post_id', 'slug', 'experiment_type', 'hypothesis', 'variant_a', 'variant_b',
        'active_variant', 'start_date', 'end_date', 'status', 'metrics', 'decision',
    ];

    protected $casts = [
        'metrics' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class, 'blog_post_id');
    }
}
