<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable([
    'name', 'email', 'password',
    'slug', 'display_name', 'tagline', 'description',
    'logo_url', 'cover_url', 'website', 'phone',
    'address', 'accent_color',
    'subscription_tier', 'subscription_until', 'is_admin',
    'instagram_handle', 'facebook_url',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const RESERVED_SLUGS = [
        'nov-oglas', 'moi-oglasi', 'profil', 'oglas', 'login',
        'register', 'logout', 'admin', 'api', 'sitemap', 'robots',
        'analitika',
    ];

    public const DEFAULT_ACCENT = '#0284c7';

    public const TIER_FREE = 'free';
    public const TIER_PRO = 'pro';
    public const TIER_PREMIUM = 'premium';

    public const TIER_LABELS = [
        self::TIER_FREE => 'Free',
        self::TIER_PRO => 'Pro',
        self::TIER_PREMIUM => 'Premium',
    ];

    public const FREE_TIER_ACTIVE_LIMIT = 3;

    protected static function booted(): void
    {
        static::creating(function (self $user) {
            $user->slug ??= self::generateUniqueSlug($user->name ?? 'agencija');
        });
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'subscription_until' => 'datetime',
            'is_admin' => 'boolean',
        ];
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class, 'agency_id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public static function generateUniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source);
        if ($base === '' || in_array($base, self::RESERVED_SLUGS, true)) {
            $base = 'agencija';
        }

        $slug = $base;
        $i = 2;
        while (
            self::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    public function getBrandNameAttribute(): string
    {
        return $this->display_name ?: $this->name;
    }

    public function getAccentAttribute(): string
    {
        return $this->accent_color ?: self::DEFAULT_ACCENT;
    }

    /**
     * The user's effective tier — falls back to "free" if a paid tier
     * has expired (subscription_until in the past).
     */
    public function effectiveTier(): string
    {
        if ($this->subscription_tier === self::TIER_FREE) {
            return self::TIER_FREE;
        }

        if ($this->subscription_until && $this->subscription_until->isPast()) {
            return self::TIER_FREE;
        }

        return $this->subscription_tier ?? self::TIER_FREE;
    }

    public function isPro(): bool
    {
        return in_array($this->effectiveTier(), [self::TIER_PRO, self::TIER_PREMIUM], true);
    }

    public function isPremium(): bool
    {
        return $this->effectiveTier() === self::TIER_PREMIUM;
    }

    /**
     * Free tier is capped at FREE_TIER_ACTIVE_LIMIT active (non-expired) listings.
     * Pro/Premium are unlimited.
     */
    public function canCreateListing(): bool
    {
        if ($this->isPro()) {
            return true;
        }

        $active = $this->listings()
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->count();

        return $active < self::FREE_TIER_ACTIVE_LIMIT;
    }

    public function activeListingCount(): int
    {
        return $this->listings()
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->count();
    }
}
