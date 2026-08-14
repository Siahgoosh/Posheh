<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommVisitorEvent extends Model
{
    public $timestamps = false;

    protected $table = 'comm_visitor_events';

    protected $fillable = [
        'visitor_id', 'session_id', 'event_type', 'path', 'meta', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(CommVisitor::class, 'visitor_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CommVisitorSession::class, 'session_id');
    }
}
