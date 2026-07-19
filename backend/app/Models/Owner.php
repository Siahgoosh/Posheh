<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Owner extends Model
{
    use BelongsToOffice;

    protected $fillable = [
        'office_id',
        'created_by',
        'name',
        'mobile',
        'national_id',
        'email',
        'address',
        'notes',
        'portal_token',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }
}
