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
        'visibility',
        'access_password',
        'share_token',
        'settings',
        'view_count',
        'published_at',
        'expires_at',
        'archived_at',
        'version',
    ];

    protected $hidden = [
        'access_password',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'archived_at' => 'datetime',
            'view_count' => 'integer',
            'version' => 'integer',
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

    public function versions(): HasMany
    {
        return $this->hasMany(VirtualTourVersion::class)->orderByDesc('version_number');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(VirtualTourActivityLog::class)->orderByDesc('created_at');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')->whereNull('archived_at');
    }

    public function scopeNotArchived($query)
    {
        return $query->whereNull('archived_at');
    }

    public function publicUrl(): string
    {
        $base = rtrim(config('app.frontend_url', config('app.url')), '/');

        return "{$base}/tour/{$this->slug}";
    }

    public function privateUrl(): string
    {
        return $this->publicUrl().'?token='.$this->share_token;
    }

    public function embedUrl(): string
    {
        $base = rtrim(config('app.frontend_url', config('app.url')), '/');

        return "{$base}/embed/tour/{$this->slug}";
    }
}
