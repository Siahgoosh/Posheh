<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommVisitorSession extends Model
{
    protected $table = 'comm_visitor_sessions';

    protected $fillable = [
        'visitor_id', 'session_key', 'current_page', 'pages_viewed',
        'time_on_site_seconds', 'scroll_depth', 'click_count', 'mouse_movement_count',
        'is_online', 'last_activity_at', 'started_at', 'ended_at', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'pages_viewed' => 'array',
            'is_online' => 'boolean',
            'last_activity_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(CommVisitor::class, 'visitor_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(CommVisitorEvent::class, 'session_id');
    }
}
