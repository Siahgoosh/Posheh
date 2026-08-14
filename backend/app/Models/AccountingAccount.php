<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountingAccount extends Model
{
    use BelongsToOffice, SoftDeletes;

    protected $fillable = [
        'office_id', 'code', 'name', 'group_key', 'type',
        'is_system', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
