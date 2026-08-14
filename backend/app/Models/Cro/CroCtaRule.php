<?php

namespace App\Models\Cro;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CroCtaRule extends Model
{
    protected $table = 'cro_cta_rules';

    protected $fillable = [
        'name', 'cro_cta_id', 'match_field', 'match_operator', 'match_value', 'priority', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function cta(): BelongsTo
    {
        return $this->belongsTo(CroCta::class, 'cro_cta_id');
    }
}
