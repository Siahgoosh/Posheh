<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;

class CrmAutomationRule extends Model
{
    use BelongsToOffice;

    protected $fillable = [
        'office_id', 'name', 'trigger', 'conditions', 'actions',
        'delay_minutes', 'is_active', 'is_system',
    ];

    protected function casts(): array
    {
        return [
            'conditions' => 'array',
            'actions' => 'array',
            'is_active' => 'boolean',
            'is_system' => 'boolean',
        ];
    }
}
