<?php

namespace App\Models\Communication;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommVisitor extends Model
{
    protected $table = 'comm_visitors';

    protected $fillable = [
        'uuid', 'user_id', 'first_name', 'last_name', 'mobile', 'email',
        'ip', 'country', 'province', 'city', 'timezone', 'language',
        'browser', 'os', 'device', 'user_agent', 'screen_resolution',
        'landing_page', 'referrer', 'utm_source', 'utm_campaign', 'utm_medium',
        'utm_term', 'utm_content', 'visit_count', 'lead_score', 'score_breakdown',
        'first_visit_at', 'last_visit_at', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'score_breakdown' => 'array',
            'metadata' => 'array',
            'first_visit_at' => 'datetime',
            'last_visit_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(CommVisitorSession::class, 'visitor_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(CommVisitorEvent::class, 'visitor_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(CommLead::class, 'visitor_id');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(CommConversation::class, 'visitor_id');
    }
}
