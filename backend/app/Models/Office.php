<?php

namespace App\Models;

use App\Enums\SubscriptionPlan as SubscriptionPlanEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Office extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'phone',
        'address',
        'city',
        'logo_path',
        'settings',
        'is_active',
        'trial_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_active' => 'boolean',
            'trial_ends_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Office $office) {
            if (empty($office->uuid)) {
                $office->uuid = (string) Str::uuid();
            }
            if (empty($office->slug)) {
                $office->slug = Str::slug($office->name).'-'.Str::random(4);
            }
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(OfficeInvitation::class);
    }

    public function healthScore(): HasOne
    {
        return $this->hasOne(OfficeHealthScore::class);
    }

    public function virtualTours(): HasMany
    {
        return $this->hasMany(VirtualTour::class);
    }

    public function hasActiveSubscription(): bool
    {
        return $this->subscription()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->exists();
    }

    public function getSetting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }
}
