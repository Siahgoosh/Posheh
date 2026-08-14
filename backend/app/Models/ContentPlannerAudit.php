<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentPlannerAudit extends Model
{
    public $timestamps = false;

    protected $table = 'content_planner_audits';

    protected $fillable = [
        'content_id',
        'office_id',
        'user_id',
        'action',
        'before',
        'after',
        'meta',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(SocialContent::class, 'content_id');
    }
}
