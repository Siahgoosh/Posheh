<?php

namespace App\Models;

use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VirtualTour extends Model
{
    use BelongsToOffice;

    protected $fillable = [
        'office_id',
        'property_id',
        'created_by',
        'title',
        'slug',
        'description',
        'status',
        'settings',
        'view_count',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'published_at' => 'datetime',
            'view_count' => 'integer',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scenes(): HasMany
    {
        return $this->hasMany(VirtualTourScene::class)->orderBy('sort_order');
    }

    public function media(): HasMany
    {
        return $this->hasMany(VirtualTourMedia::class)->orderBy('sort_order');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(VirtualTourLead::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(VirtualTourView::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function publicUrl(): string
    {
        $base = rtrim(config('app.frontend_url', config('app.url')), '/');

        return "{$base}/tour/{$this->slug}";
    }
}
