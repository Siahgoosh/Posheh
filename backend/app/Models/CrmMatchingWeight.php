<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;

class CrmMatchingWeight extends Model
{
    use BelongsToOffice;

    protected $fillable = ['office_id', 'key', 'label', 'weight', 'is_active'];

    protected function casts(): array
    {
        return ['weight' => 'integer', 'is_active' => 'boolean'];
    }
}
