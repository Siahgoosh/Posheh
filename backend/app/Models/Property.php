<?php

namespace App\Models;

use App\Enums\PropertyPermission;
use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use App\Traits\BelongsToOffice;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use BelongsToOffice, HasFactory, SoftDeletes;

    protected $fillable = [
        'office_id', 'created_by', 'assigned_to', 'code', 'title', 'type', 'building_type',
        'deed_type', 'direction', 'permission', 'status', 'owner_name', 'owner_mobile',
        'owner_contact_id', 'price', 'price_per_meter', 'deposit', 'rent', 'is_negotiable',
        'commission_percent', 'source', 'area', 'land_area', 'rooms', 'building_age',
        'renovation_status', 'floor', 'total_floors', 'units_per_floor', 'has_parking',
        'has_elevator', 'has_storage', 'heating_type', 'cooling_type', 'province', 'city',
        'district', 'neighborhood', 'address', 'latitude', 'longitude', 'description',
        'internal_notes', 'features', 'amenities', 'expires_at', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => PropertyType::class,
            'permission' => PropertyPermission::class,
            'status' => PropertyStatus::class,
            'has_parking' => 'boolean',
            'has_elevator' => 'boolean',
            'has_storage' => 'boolean',
            'is_negotiable' => 'boolean',
            'features' => 'array',
            'amenities' => 'array',
            'expires_at' => 'datetime',
            'published_at' => 'datetime',
            'area' => 'decimal:2',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function ownerContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'owner_contact_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(PropertyMedia::class)->orderBy('sort_order');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(PropertyFavorite::class);
    }

    public function coverImage(): ?PropertyMedia
    {
        return $this->media()->where('is_cover', true)->first()
            ?? $this->media()->where('type', 'image')->first();
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->canManageOffice()) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->where('permission', PropertyPermission::Office)
                ->orWhere('permission', PropertyPermission::Team)
                ->orWhere(function ($q2) use ($user) {
                    $q2->where('permission', PropertyPermission::Private)
                        ->where('created_by', $user->id);
                })
                ->orWhere(function ($q2) use ($user) {
                    $q2->where('permission', PropertyPermission::Private)
                        ->where('assigned_to', $user->id);
                });
        })->where('permission', '!=', PropertyPermission::ManagerOnly);
    }

    public function scopeActive($query)
    {
        return $query->where('status', PropertyStatus::Active);
    }

    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->where('status', PropertyStatus::Active)
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays($days)]);
    }
}
