<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;

class CrmAutomationLog extends Model
{
    use BelongsToOffice;

    protected $fillable = [
        'office_id', 'automation_rule_id', 'trigger', 'subject_type',
        'subject_id', 'reason', 'actions_taken',
    ];

    protected function casts(): array
    {
        return [
            'reason' => 'array',
            'actions_taken' => 'array',
        ];
    }
}
