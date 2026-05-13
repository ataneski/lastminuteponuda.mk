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
     * Catalog of every feature flag a tier may toggle. Admin UI renders
     * one row per item, grouped by `group`. Runtime code reads values
     * via {@see User::feature()} so all gates respect the data, not
     * hard-coded constants.
     *
     * type:
     *   - bool          → on/off toggle
     *   - int           → integer ≥ 0
     *   - nullable_int  → integer or "unlimited" (null)
     */
    public const FEATURES = [
        // ─── Лимити ─────────────────────────────────────────────────
        'max_active_listings' => [
            'type' => 'nullable_int', 'group' => 'limits',
            'label' => 'Максимум активни огласи',
            'help' => 'Празно = неограничено (∞)',
        ],
        'max_images_per_listing' => [
            'type' => 'int', 'group' => 'limits',
            'label' => 'Максимум слики по оглас',
            'help' => 'Колку фотографии може да се прикачат на еден оглас',
        ],
        'max_draft_listings' => [
            'type' => 'int', 'group' => 'limits',
            'label' => 'Максимум draft-ови (24h)',
            'help' => 'Колку нерасчистени AI/WhatsApp draft-ови може да виси',
        ],

        // ─── Маркетинг ──────────────────────────────────────────────
        'featured_boosts_per_month' => [
            'type' => 'int', 'group' => 'marketing',
            'label' => 'Featured boost-и / месец',
            'help' => 'Колку огласи може да биде на врвот без доплата',
        ],
        'social_posts_per_month' => [
            'type' => 'int', 'group' => 'marketing',
            'label' => 'IG/FB post-и / месец',
            'help' => 'Колку post-ови нашиот тим прави за нив',
        ],
        'homepage_featured' => [
            'type' => 'bool', 'group' => 'marketing',
            'label' => 'Херо банер на почетна',
            'help' => 'Гарантирана видливост на /',
        ],

        // ─── Алатки ─────────────────────────────────────────────────
        'ai_wizard' => [
            'type' => 'bool', 'group' => 'tools',
            'label' => 'AI wizard',
            'help' => 'Креирање оглас со AI обработка на слики и наслов',
        ],
        'whatsapp_intake' => [
            'type' => 'bool', 'group' => 'tools',
            'label' => 'WhatsApp директен upload',
            'help' => 'Праќање слики директно преку WhatsApp Business',
        ],
        'bulk_import' => [
            'type' => 'bool', 'group' => 'tools',
            'label' => 'Bulk import (CSV)',
            'help' => 'Внес на повеќе огласи одеднаш',
        ],
        'api_access' => [
            'type' => 'bool', 'group' => 'tools',
            'label' => 'REST API пристап',
            'help' => 'За интеграција со постоечки агенциски систем',
        ],

        // ─── Аналитика ──────────────────────────────────────────────
        'analytics_enabled' => [
            'type' => 'bool', 'group' => 'analytics',
            'label' => 'Аналитика (agency-wide)',
            'help' => 'KPI dashboard со прегледи, прашања, конверзија',
        ],
        'per_listing_analytics' => [
            'type' => 'bool', 'group' => 'analytics',
            'label' => 'Per-listing аналитика',
            'help' => '7/30/90-дневен chart + inquiry feed по оглас',
        ],

        // ─── Брендирање ─────────────────────────────────────────────
        'custom_branding' => [
            'type' => 'bool', 'group' => 'branding',
            'label' => 'Целосно брендирање',
            'help' => 'Cover, custom slug, акцент боја, лого',
        ],
        'verified_badge' => [
            'type' => 'bool', 'group' => 'branding',
            'label' => 'Verified badge',
            'help' => 'Сино „верифицирана" значка на огласите',
        ],

        // ─── Поддршка ───────────────────────────────────────────────
        'priority_support' => [
            'type' => 'bool', 'group' => 'support',
            'label' => 'Приоритетна поддршка',
            'help' => 'Email + телефон со 24h одговор',
        ],
        'dedicated_account_manager' => [
            'type' => 'bool', 'group' => 'support',
            'label' => 'Сопствен менаџер',
            'help' => 'Лична контакт точка кај нас',
        ],
    ];

    /** Group labels for the admin UI. */
    public const GROUP_LABELS = [
        'limits' => 'Лимити',
        'marketing' => 'Маркетинг',
        'tools' => 'Алатки',
        'analytics' => 'Аналитика',
        'branding' => 'Брендирање',
        'support' => 'Поддршка',
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
