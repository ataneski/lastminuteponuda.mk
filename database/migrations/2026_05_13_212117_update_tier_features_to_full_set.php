<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Expand free/pro/premium feature blobs to the new 15-key catalogue.
     * Missing keys on existing rows would otherwise fall through to the
     * fallback defaults; setting them explicitly here makes admin UI
     * show meaningful values out of the box.
     */
    public function up(): void
    {
        $matrix = [
            'free' => [
                'max_active_listings' => 3,
                'max_images_per_listing' => 5,
                'max_draft_listings' => 3,
                'featured_boosts_per_month' => 0,
                'social_posts_per_month' => 0,
                'homepage_featured' => false,
                'ai_wizard' => true,
                'whatsapp_intake' => false,
                'bulk_import' => false,
                'api_access' => false,
                'analytics_enabled' => false,
                'per_listing_analytics' => false,
                'custom_branding' => false,
                'verified_badge' => false,
                'priority_support' => false,
                'dedicated_account_manager' => false,
            ],
            'pro' => [
                'max_active_listings' => null,
                'max_images_per_listing' => 10,
                'max_draft_listings' => 10,
                'featured_boosts_per_month' => 1,
                'social_posts_per_month' => 1,
                'homepage_featured' => false,
                'ai_wizard' => true,
                'whatsapp_intake' => true,
                'bulk_import' => false,
                'api_access' => false,
                'analytics_enabled' => true,
                'per_listing_analytics' => true,
                'custom_branding' => true,
                'verified_badge' => false,
                'priority_support' => false,
                'dedicated_account_manager' => false,
            ],
            'premium' => [
                'max_active_listings' => null,
                'max_images_per_listing' => 20,
                'max_draft_listings' => 20,
                'featured_boosts_per_month' => 4,
                'social_posts_per_month' => 4,
                'homepage_featured' => true,
                'ai_wizard' => true,
                'whatsapp_intake' => true,
                'bulk_import' => true,
                'api_access' => true,
                'analytics_enabled' => true,
                'per_listing_analytics' => true,
                'custom_branding' => true,
                'verified_badge' => true,
                'priority_support' => true,
                'dedicated_account_manager' => false,
            ],
        ];

        foreach ($matrix as $key => $features) {
            DB::table('tiers')
                ->where('key', $key)
                ->update(['features' => json_encode($features), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // No-op; keeping the expanded feature set is harmless on rollback.
    }
};
