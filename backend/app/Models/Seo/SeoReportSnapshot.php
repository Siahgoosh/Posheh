<?php

namespace App\Models\Seo;

use Illuminate\Database\Eloquent\Model;

class SeoReportSnapshot extends Model
{
    protected $table = 'seo_report_snapshots';

    protected $fillable = ['period_type', 'period_start', 'period_end', 'payload'];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'payload' => 'array',
    ];
}
