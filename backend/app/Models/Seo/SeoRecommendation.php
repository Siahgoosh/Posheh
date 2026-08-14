<?php

namespace App\Models\Seo;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoRecommendation extends Model
{
    protected $table = 'seo_recommendations';

    public const STATUSES = ['NEW', 'REVIEWED', 'APPROVED', 'REJECTED', 'EXECUTED', 'ROLLED_BACK'];

    protected $fillable = [
        'seo_opportunity_id', 'action_type', 'automation_level', 'priority', 'problem',
        'evidence', 'recommendation', 'expected_benefit', 'risk', 'effort', 'priority_score',
        'status', 'approved_by', 'approved_at', 'executed_at', 'rolled_back_at',
        'before_snapshot', 'after_snapshot', 'result_notes', 'payload',
    ];

    protected $casts = [
        'before_snapshot' => 'array',
        'after_snapshot' => 'array',
        'payload' => 'array',
        'approved_at' => 'datetime',
        'executed_at' => 'datetime',
        'rolled_back_at' => 'datetime',
        'priority_score' => 'float',
    ];

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(SeoOpportunity::class, 'seo_opportunity_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
