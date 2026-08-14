<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentPlannerTemplate extends Model
{
    use BelongsToOffice;
    use SoftDeletes;

    protected $table = 'content_planner_templates';

    protected $fillable = [
        'office_id',
        'user_id',
        'name',
        'content_type',
        'platforms',
        'goal',
        'hook',
        'body',
        'cta',
        'caption',
        'hashtags',
        'visual_idea',
        'overlay_text',
        'is_shared',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'platforms' => 'array',
            'hashtags' => 'array',
            'is_shared' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
