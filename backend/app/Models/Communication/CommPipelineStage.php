<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommPipelineStage extends Model
{
    protected $table = 'comm_pipeline_stages';

    protected $fillable = [
        'pipeline_id', 'name', 'slug', 'color', 'sort_order', 'is_won', 'is_lost',
    ];

    protected function casts(): array
    {
        return [
            'is_won' => 'boolean',
            'is_lost' => 'boolean',
        ];
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(CommPipeline::class, 'pipeline_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(CommLead::class, 'pipeline_stage_id');
    }
}
