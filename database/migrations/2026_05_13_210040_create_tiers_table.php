<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tiers', function (Blueprint $table) {
            $table->id();
            $table->string('key', 32)->unique();          // 'free', 'pro', 'premium' or custom
            $table->string('name', 60);
            $table->unsignedInteger('monthly_price')->default(0);
            $table->string('currency', 3)->default('MKD');
            $table->json('features');                      // map of feature_key => value
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed the three default tiers matching the previous hard-coded behaviour.
        $now = now();
        DB::table('tiers')->insert([
            [
                'key' => 'free',
                'name' => 'Free',
                'monthly_price' => 0,
                'currency' => 'MKD',
                'features' => json_encode([
                    'max_active_listings' => 3,
                    'featured_boosts_per_month' => 0,
                    'analytics_enabled' => false,
                    'social_posts_per_month' => 0,
                    'verified_badge' => false,
                    'whatsapp_intake' => false,
                    'ai_wizard' => true,
                    'custom_branding' => false,
                ]),
                'active' => true,
                'sort_order' => 0,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'key' => 'pro',
                'name' => 'Pro',
                'monthly_price' => 990,
                'currency' => 'MKD',
                'features' => json_encode([
                    'max_active_listings' => null,
                    'featured_boosts_per_month' => 1,
                    'analytics_enabled' => true,
                    'social_posts_per_month' => 1,
                    'verified_badge' => false,
                    'whatsapp_intake' => true,
                    'ai_wizard' => true,
                    'custom_branding' => true,
                ]),
                'active' => true,
                'sort_order' => 10,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'key' => 'premium',
                'name' => 'Premium',
                'monthly_price' => 2490,
                'currency' => 'MKD',
                'features' => json_encode([
                    'max_active_listings' => null,
                    'featured_boosts_per_month' => 4,
                    'analytics_enabled' => true,
                    'social_posts_per_month' => 4,
                    'verified_badge' => true,
                    'whatsapp_intake' => true,
                    'ai_wizard' => true,
                    'custom_branding' => true,
                ]),
                'active' => true,
                'sort_order' => 20,
                'created_at' => $now, 'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tiers');
    }
};
