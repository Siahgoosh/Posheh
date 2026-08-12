<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;

class CrmOnboardingItem extends Model
{
    use BelongsToOffice;

    protected $table = 'crm_onboarding_checklist';

    protected $fillable = [
        'office_id', 'key', 'label', 'is_done', 'done_at', 'done_by', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['is_done' => 'boolean', 'done_at' => 'datetime'];
    }
}
