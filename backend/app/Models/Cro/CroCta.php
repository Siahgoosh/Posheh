<?php

namespace App\Models\Cro;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CroCta extends Model
{
    protected $table = 'cro_ctas';

    protected $fillable = [
        'key', 'title', 'description', 'button_text', 'url', 'image', 'type',
        'category', 'topic', 'funnel_stage', 'intent', 'priority', 'is_active', 'meta',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function rules(): HasMany
    {
        return $this->hasMany(CroCtaRule::class, 'cro_cta_id');
    }
}
