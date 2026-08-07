<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CommTag extends Model
{
    protected $table = 'comm_tags';

    protected $fillable = ['name', 'color'];

    public function leads(): BelongsToMany
    {
        return $this->belongsToMany(CommLead::class, 'comm_lead_tag', 'tag_id', 'lead_id');
    }
}
