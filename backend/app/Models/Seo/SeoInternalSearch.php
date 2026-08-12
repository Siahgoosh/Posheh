<?php

namespace App\Models\Seo;

use Illuminate\Database\Eloquent\Model;

class SeoInternalSearch extends Model
{
    protected $table = 'seo_internal_searches';

    protected $fillable = [
        'query_raw', 'query_normalized', 'results_count', 'zero_result',
        'clicked_slug', 'ip_hash', 'searched_at',
    ];

    protected $casts = [
        'zero_result' => 'boolean',
        'searched_at' => 'datetime',
    ];
}
