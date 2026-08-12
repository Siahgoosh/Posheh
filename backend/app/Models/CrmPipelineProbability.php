<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;

class CrmPipelineProbability extends Model
{
    use BelongsToOffice;

    protected $fillable = ['office_id', 'stage_key', 'probability'];

    protected function casts(): array
    {
        return ['probability' => 'integer'];
    }
}
