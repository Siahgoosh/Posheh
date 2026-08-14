<?php

namespace App\Models\Cro;

use Illuminate\Database\Eloquent\Model;

class CroPageGoal extends Model
{
    protected $table = 'cro_page_goals';

    protected $fillable = [
        'path', 'primary_goal', 'secondary_goal', 'primary_cta_key', 'secondary_cta_key',
        'conversion_type', 'funnel_stage', 'business_value',
    ];
}
