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
        'subdomain',
        'subscription_plan_id',
        'plan_active',
        'panel_type',
        'phone',
        'address',
        'city',
        'logo_path',
        'settings',
        'is_active',
        'is_verified',
        'show_on_website',
        'website_status',
        'website_description',
        'website_published_at',
        'custom_domain',
        'custom_domain_status',
        'domain_dns_token',
        'telegram_bot_token',
        'telegram_admin_chat_id',
        'whatsapp_config',
        'description',
        'trial_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'whatsapp_config' => 'array',
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
            'show_on_website' => 'boolean',
            'plan_active' => 'boolean',
            'website_published_at' => 'datetime',
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

    public function virtualTours(): HasMany
    {
        return $this->hasMany(VirtualTour::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function plan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
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

    public function hasActiveSubscription(): bool
    {
        return $this->subscription()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->exists();
    }

    public function onTrial(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
    }

    public function getSetting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }
}
