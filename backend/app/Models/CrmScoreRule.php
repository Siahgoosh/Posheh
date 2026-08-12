<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;

class CrmScoreRule extends Model
{
    use BelongsToOffice;

    protected $fillable = ['office_id', 'key', 'label', 'points', 'is_active'];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
