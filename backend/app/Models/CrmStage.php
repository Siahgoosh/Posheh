<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmStage extends Model
{
    use BelongsToOffice;

    protected $fillable = [
        'office_id',
        'name',
        'color',
        'sort_order',
        'is_won',
        'is_lost',
    ];

    protected function casts(): array
    {
        return [
            'is_won' => 'boolean',
            'is_lost' => 'boolean',
        ];
    }

    public function deals(): HasMany
    {
        return $this->hasMany(CrmDeal::class, 'stage_id')->orderBy('sort_order');
    }
}
