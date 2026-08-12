<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class CrmTag extends Model
{
    use BelongsToOffice;

    protected $fillable = ['office_id', 'name', 'color'];

    public function customers(): MorphToMany
    {
        return $this->morphedByMany(Customer::class, 'taggable', 'crm_taggables');
    }

    public function deals(): MorphToMany
    {
        return $this->morphedByMany(CrmDeal::class, 'taggable', 'crm_taggables');
    }
}
