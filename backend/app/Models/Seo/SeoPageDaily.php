<?php

namespace App\Models\Seo;

use Illuminate\Database\Eloquent\Model;

class SeoPageDaily extends Model
{
    protected $table = 'seo_page_daily';

    protected $fillable = [
        'date', 'page_url', 'slug', 'clicks', 'impressions', 'ctr', 'position',
    ];

    protected $casts = [
        'date' => 'date',
        'ctr' => 'float',
        'position' => 'float',
    ];
}
