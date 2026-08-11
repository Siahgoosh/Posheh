<?php

namespace App\Models\Communication;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommLead extends Model
{
    use SoftDeletes;

    protected $table = 'comm_leads';

    protected $fillable = [
        'visitor_id', 'pipeline_stage_id', 'assigned_to',
        'first_name', 'last_name', 'mobile', 'mobile_verified', 'email',
        'province', 'city', 'office_name', 'role_title', 'staff_count',
        'activity_type', 'request_type', 'budget', 'description',
        'source_channel', 'status', 'lead_score', 'score_breakdown',
        'ip', 'country', 'tracking_snapshot', 'follow_up_at', 'converted_at',
    ];

    protected function casts(): array
    {
        return [
            'mobile_verified' => 'boolean',
            'score_breakdown' => 'array',
            'tracking_snapshot' => 'array',
            'follow_up_at' => 'datetime',
            'converted_at' => 'datetime',
        ];
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(CommVisitor::class, 'visitor_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(CommPipelineStage::class, 'pipeline_stage_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(CommTag::class, 'comm_lead_tag', 'lead_id', 'tag_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(CommLeadNote::class, 'lead_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(CommLeadTask::class, 'lead_id');
    }

    public function conversation(): HasOne
    {
        return $this->hasOne(CommConversation::class, 'lead_id');
    }
}
