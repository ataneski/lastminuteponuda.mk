<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A subscription tier that an agency can be assigned to. Tiers are
 * fully editable in admin — features is a JSON map where the admin
 * toggles individual capabilities. Hard-coded fallback values exist
 * in {@see User} so a missing tier never crashes the runtime.
 */
class Tier extends Model
{
    /**
     * Known feature keys + their type signature. Admin UI renders a
     * row per key; the value is interpreted by User->hasFeature(...).
     *
     * type: 'bool' | 'int' | 'nullable_int'
     *   - bool          → on/off toggle
     *   - int           → number ≥ 0
     *   - nullable_int  → number or "unlimited" (null)
     */
    public const FEATURES = [
        'max_active_listings'        => ['type' => 'nullable_int', 'label' => 'Максимум активни огласи (празно = unlimited)'],
        'featured_boosts_per_month'  => ['type' => 'int',          'label' => 'Featured boost-и / месец'],
        'analytics_enabled'          => ['type' => 'bool',         'label' => 'Аналитика'],
        'social_posts_per_month'     => ['type' => 'int',          'label' => 'IG/FB post-и / месец'],
        'verified_badge'             => ['type' => 'bool',         'label' => 'Verified badge'],
        'whatsapp_intake'            => ['type' => 'bool',         'label' => 'WhatsApp ingestion'],
        'ai_wizard'                  => ['type' => 'bool',         'label' => 'AI wizard за нов оглас'],
        'custom_branding'            => ['type' => 'bool',         'label' => 'Cover + custom slug + акцент боја'],
    ];

    protected $fillable = [
        'key', 'name', 'monthly_price', 'currency',
        'features', 'active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'active' => 'boolean',
            'monthly_price' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'key';
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'subscription_tier', 'key');
    }

    public function feature(string $key, mixed $default = null): mixed
    {
        // Use array_key_exists, NOT `?? $default` — a feature can be
        // intentionally null (e.g. max_active_listings = null = unlimited).
        $features = $this->features ?? [];

        return array_key_exists($key, $features) ? $features[$key] : $default;
    }

    public function isPaid(): bool
    {
        return $this->monthly_price > 0;
    }
}
