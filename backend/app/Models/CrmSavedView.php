<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;

class CrmSavedView extends Model
{
    use BelongsToOffice;

    protected $fillable = [
        'office_id', 'user_id', 'name', 'entity', 'filters', 'is_shared',
    ];

    protected function casts(): array
    {
        return ['filters' => 'array', 'is_shared' => 'boolean'];
    }
}
