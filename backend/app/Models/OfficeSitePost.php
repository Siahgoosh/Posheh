<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficeSitePost extends Model
{
    use BelongsToOffice;

    protected $fillable = [
        'office_id', 'property_id', 'created_by', 'title', 'slug',
        'excerpt', 'body', 'is_published', 'views',
    ];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
