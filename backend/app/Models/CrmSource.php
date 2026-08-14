<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;

class CrmSource extends Model
{
    use BelongsToOffice;

    protected $fillable = [
        'office_id', 'key', 'label', 'is_active', 'is_system', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_system' => 'boolean',
        ];
    }
}
