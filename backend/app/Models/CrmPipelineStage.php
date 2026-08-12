<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;

class CrmPipelineStage extends Model
{
    use BelongsToOffice;

    protected $fillable = [
        'office_id', 'key', 'label', 'sort_order', 'color',
        'is_won', 'is_lost', 'is_active', 'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_won' => 'boolean',
            'is_lost' => 'boolean',
            'is_active' => 'boolean',
            'is_system' => 'boolean',
        ];
    }
}
