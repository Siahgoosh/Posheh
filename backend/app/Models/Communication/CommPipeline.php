<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommPipeline extends Model
{
    protected $table = 'comm_pipelines';

    protected $fillable = ['name', 'is_default', 'sort_order'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function stages(): HasMany
    {
        return $this->hasMany(CommPipelineStage::class, 'pipeline_id')->orderBy('sort_order');
    }
}
