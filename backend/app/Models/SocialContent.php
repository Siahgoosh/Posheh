<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SocialContent extends Model
{
    use BelongsToOffice;
    use SoftDeletes;

    protected $table = 'social_contents';

    protected $fillable = [
        'office_id',
        'user_id',
        'title',
        'topic',
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
        'location',
        'notes',
        'scheduled_at_utc',
        'timezone',
        'status',
        'reminder_enabled',
        'reminder_offset_minutes',
        'reminder_custom_minutes',
        'published_at_utc',
        'cancelled_at_utc',
        'template_id',
    ];

    protected function casts(): array
    {
        return [
            'platforms' => 'array',
            'hashtags' => 'array',
            'scheduled_at_utc' => 'datetime',
            'published_at_utc' => 'datetime',
            'cancelled_at_utc' => 'datetime',
            'reminder_enabled' => 'boolean',
            'reminder_offset_minutes' => 'integer',
            'reminder_custom_minutes' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(SocialContentMedia::class, 'content_id')->orderBy('sort_order');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(ContentReminder::class, 'content_id');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(ContentPlannerAudit::class, 'content_id');
    }

    public function isScheduledLike(): bool
    {
        return in_array($this->status, ['scheduled', 'reminder_sent'], true);
    }
}
