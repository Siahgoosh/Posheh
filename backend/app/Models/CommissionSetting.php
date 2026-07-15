<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionSetting extends Model
{
    use BelongsToOffice;

    protected $fillable = [
        'office_id',
        'sale_rate_percent',
        'rent_rate_percent',
    ];
}
