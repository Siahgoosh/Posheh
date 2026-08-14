<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentReminder extends Model
{
    protected $table = 'content_reminders';

    protected $fillable = [
        'content_id',
        'office_id',
        'user_id',
        'reminder_type',
        'channel',
        'offset_minutes',
        'scheduled_at_utc',
        'sent_at_utc',
        'status',
        'failure_reason',
        'provider_response',
        'attempt_count',
        'idempotency_key',
        'locked_at',
        'locked_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at_utc' => 'datetime',
            'sent_at_utc' => 'datetime',
            'locked_at' => 'datetime',
            'offset_minutes' => 'integer',
            'attempt_count' => 'integer',
        ];
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(SocialContent::class, 'content_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
