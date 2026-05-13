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
    'first_name', 'last_name', 'role',
    'marketing_consent', 'marketing_consent_at',
    'provider', 'provider_id', 'provider_avatar',
    'slug', 'display_name', 'tagline', 'description',
    'logo_url', 'cover_url', 'website', 'phone',
    'address', 'accent_color',
    'subscription_tier', 'subscription_until', 'is_admin',
    'instagram_handle', 'facebook_url',
    'wa_phone_e164', 'wa_pairing_code', 'wa_paired_at',
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

    public const ROLE_AGENCY = 'agency';

    public const ROLE_CUSTOMER = 'customer';

    public const ROLE_ADMIN = 'admin';

    public const TIER_FREE = 'free';
    public const TIER_PRO = 'pro';
    public const TIER_PREMIUM = 'premium';

    public const TIER_LABELS = [
        self::TIER_FREE => 'Free',
        self::TIER_PRO => 'Pro',
        self::TIER_PREMIUM => 'Premium',
    ];

    /**
     * Fallback feature values used when a user's tier is missing from
     * the tiers table (e.g. seed data, edge cases during admin edits).
     * Mirrors the seeded 'free' tier.
     */
    public const FALLBACK_FREE_FEATURES = [
        'max_active_listings'       => 3,
        'max_images_per_listing'    => 5,
        'max_draft_listings'        => 3,
        'featured_boosts_per_month' => 0,
        'social_posts_per_month'    => 0,
        'homepage_featured'         => false,
        'ai_wizard'                 => true,
        'whatsapp_intake'           => false,
        'bulk_import'               => false,
        'api_access'                => false,
        'analytics_enabled'         => false,
        'per_listing_analytics'     => false,
        'custom_branding'           => false,
        'verified_badge'            => false,
        'priority_support'          => false,
        'dedicated_account_manager' => false,
    ];

    public const FREE_TIER_ACTIVE_LIMIT = 3;

    protected static function booted(): void
    {
        static::creating(function (self $user) {
            // Customers don't need a public slug (no /agencija/{slug} page).
            if (($user->role ?? self::ROLE_AGENCY) !== self::ROLE_CUSTOMER) {
                $user->slug ??= self::generateUniqueSlug($user->name ?? 'agencija');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'subscription_until' => 'datetime',
            'is_admin' => 'boolean',
            'suspended_at' => 'datetime',
            'marketing_consent' => 'boolean',
            'marketing_consent_at' => 'datetime',
            'wa_paired_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN || (bool) $this->is_admin;
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function tier(): ?Tier
    {
        if (! $this->subscription_tier) {
            return null;
        }
        // Cached on the user instance per request.
        return $this->relationLoaded('tierModel')
            ? $this->getRelation('tierModel')
            : Tier::where('key', $this->subscription_tier)->first();
    }

    public function feature(string $key, mixed $default = null): mixed
    {
        $tier = $this->tier();
        if ($tier) {
            $sentinel = new \stdClass; // unique marker; can't collide with stored value
            $value = $tier->feature($key, $sentinel);
            if ($value !== $sentinel) {
                return $value;
            }
        }

        return array_key_exists($key, self::FALLBACK_FREE_FEATURES)
            ? self::FALLBACK_FREE_FEATURES[$key]
            : $default;
    }

    public function isAgency(): bool
    {
        return $this->role === self::ROLE_AGENCY;
    }

    public function isCustomer(): bool
    {
        return $this->role === self::ROLE_CUSTOMER;
    }

    public function getFullNameAttribute(): string
    {
        if ($this->first_name || $this->last_name) {
            return trim("{$this->first_name} {$this->last_name}");
        }

        return $this->name ?? '';
    }

    public function whatsappSessions(): HasMany
    {
        return $this->hasMany(WhatsAppIntakeSession::class);
    }

    public function isWhatsAppPaired(): bool
    {
        return $this->wa_phone_e164 !== null && $this->wa_paired_at !== null;
    }

    public function regenerateWhatsAppPairingCode(): string
    {
        $code = strtoupper(\Illuminate\Support\Str::random(6));
        $this->forceFill([
            'wa_pairing_code' => $code,
            'wa_phone_e164' => null,
            'wa_paired_at' => null,
        ])->save();

        return $code;
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
     * The user's effective tier KEY — falls back to "free" if the
     * subscription has expired (subscription_until in the past).
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
        // "Pro" semantically = unlimited listings AND analytics. Read from tier features.
        $effective = $this->effectiveTier();
        if ($effective === self::TIER_FREE) {
            return false;
        }
        // If we can't load the tier (e.g. legacy), trust the key name.
        $tier = $this->tier();
        if (! $tier) {
            return in_array($effective, [self::TIER_PRO, self::TIER_PREMIUM], true);
        }

        return $tier->feature('max_active_listings') === null
            && $tier->feature('analytics_enabled') === true;
    }

    public function isPremium(): bool
    {
        return $this->effectiveTier() === self::TIER_PREMIUM;
    }

    /**
     * Listing cap: if the tier has max_active_listings === null, unlimited.
     * Otherwise count active (published + non-expired) + recent drafts
     * against the configured ceiling.
     */
    public function canCreateListing(): bool
    {
        $cap = $this->feature('max_active_listings');
        if ($cap === null) {
            return true;
        }

        $active = $this->listings()
            ->where(function ($q) {
                $q->whereNotNull('published_at')
                    ->where(function ($qq) {
                        $qq->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    });
            })
            ->count();

        $recentDrafts = $this->listings()
            ->whereNull('published_at')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        return ($active + $recentDrafts) < (int) $cap;
    }

    public function activeListingCount(): int
    {
        return $this->listings()
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->count();
    }

    public function suspend(?string $reason = null): void
    {
        $this->forceFill([
            'suspended_at' => now(),
            'suspension_reason' => $reason,
        ])->save();
    }

    public function unsuspend(): void
    {
        $this->forceFill([
            'suspended_at' => null,
            'suspension_reason' => null,
        ])->save();
    }
}
