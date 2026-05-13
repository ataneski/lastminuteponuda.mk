<?php

use App\Models\Listing;
use App\Models\Tier;
use App\Models\User;

it('catalogue lists all 16 feature keys in 6 groups', function () {
    expect(count(Tier::FEATURES))->toBe(16);
    expect(array_keys(Tier::GROUP_LABELS))->toBe([
        'limits', 'marketing', 'tools', 'analytics', 'branding', 'support',
    ]);

    // Every feature must declare a known group + type.
    foreach (Tier::FEATURES as $key => $meta) {
        expect($meta)->toHaveKeys(['type', 'group', 'label']);
        expect(in_array($meta['type'], ['bool', 'int', 'nullable_int'], true))->toBeTrue();
        expect(array_key_exists($meta['group'], Tier::GROUP_LABELS))->toBeTrue();
    }
});

it('seeded pro tier exposes unlimited listings + analytics', function () {
    $pro = Tier::where('key', 'pro')->firstOrFail();

    expect($pro->feature('max_active_listings'))->toBeNull();
    expect($pro->feature('analytics_enabled'))->toBeTrue();
    expect($pro->feature('per_listing_analytics'))->toBeTrue();
    expect($pro->feature('whatsapp_intake'))->toBeTrue();
    expect($pro->feature('custom_branding'))->toBeTrue();
    expect($pro->feature('verified_badge'))->toBeFalse(); // only premium
});

it('seeded premium tier has all premium-only flags on', function () {
    $premium = Tier::where('key', 'premium')->firstOrFail();

    expect($premium->feature('verified_badge'))->toBeTrue();
    expect($premium->feature('priority_support'))->toBeTrue();
    expect($premium->feature('bulk_import'))->toBeTrue();
    expect($premium->feature('api_access'))->toBeTrue();
    expect($premium->feature('homepage_featured'))->toBeTrue();
});

it('free tier has analytics_enabled=false and per_listing_analytics=false', function () {
    $free = Tier::where('key', 'free')->firstOrFail();
    expect($free->feature('analytics_enabled'))->toBeFalse();
    expect($free->feature('per_listing_analytics'))->toBeFalse();
});

it('per-listing analytics page respects per_listing_analytics flag, not isPro', function () {
    // Custom tier with analytics_enabled=true but per_listing_analytics=false
    Tier::create([
        'key' => 'analytics-only',
        'name' => 'Analytics Only',
        'monthly_price' => 100,
        'currency' => 'MKD',
        'features' => [
            'max_active_listings' => null,
            'analytics_enabled' => true,
            'per_listing_analytics' => false,
        ],
        'active' => true,
        'sort_order' => 5,
    ]);

    $user = User::factory()->create([
        'subscription_tier' => 'analytics-only',
        'subscription_until' => now()->addYear(),
    ]);
    $listing = Listing::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('listings.analytics', $listing))
        ->assertOk()
        ->assertSee('не е дел од твојот план');
});

it('agency-wide analytics respects analytics_enabled flag', function () {
    $user = User::factory()->create([
        'subscription_tier' => 'free',
    ]);
    $this->actingAs($user)
        ->get(route('agency.analytics'))
        ->assertOk()
        ->assertSee('не е дел од твојот план');

    // Turn ON for free tier and reload
    Tier::where('key', 'free')->update([
        'features' => json_encode(array_merge(
            Tier::where('key', 'free')->first()->features,
            ['analytics_enabled' => true]
        )),
    ]);
    $user->load([]); // ensure tier re-fetches

    $this->actingAs($user)
        ->get(route('agency.analytics'))
        ->assertOk()
        ->assertSee('Активни огласи');
});

it('verified_badge shows on agency public page when enabled', function () {
    $premium = User::factory()->create([
        'subscription_tier' => 'premium',
        'subscription_until' => now()->addYear(),
        'name' => 'Verified Agency',
    ]);

    $this->get(route('agency.show', $premium))
        ->assertOk()
        ->assertSee('Verified');

    $free = User::factory()->create([
        'subscription_tier' => 'free',
        'name' => 'Basic Agency',
    ]);

    $this->get(route('agency.show', $free))
        ->assertOk()
        ->assertDontSee('Verified');
});

it('ai wizard route 302s to /planovi when ai_wizard feature is off', function () {
    Tier::where('key', 'free')->update([
        'features' => json_encode(array_merge(
            Tier::where('key', 'free')->first()->features,
            ['ai_wizard' => false]
        )),
    ]);

    $user = User::factory()->create(['subscription_tier' => 'free']);

    $this->actingAs($user)
        ->get('/agencija/nov-oglas-ai')
        ->assertRedirect(route('upgrade'));
});

it('admin tiers page renders with grouped feature labels', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.tiers'))
        ->assertOk()
        ->assertSee('Лимити')
        ->assertSee('Маркетинг')
        ->assertSee('Алатки')
        ->assertSee('Аналитика')
        ->assertSee('Брендирање')
        ->assertSee('Поддршка')
        ->assertSee('Максимум активни огласи');
});

it('admin tier create accepts the full 16-key feature set', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $features = [];
    foreach (Tier::FEATURES as $key => $meta) {
        $features[$key] = match ($meta['type']) {
            'bool' => '1',
            'int' => '5',
            'nullable_int' => '',
        };
    }

    $this->actingAs($admin)
        ->post(route('admin.tiers.store'), [
            'key' => 'enterprise',
            'name' => 'Enterprise',
            'monthly_price' => 9990,
            'currency' => 'MKD',
            'active' => '1',
            'sort_order' => 100,
            'features' => $features,
        ])
        ->assertRedirect(route('admin.tiers'));

    $tier = Tier::where('key', 'enterprise')->firstOrFail();
    expect($tier->feature('max_active_listings'))->toBeNull();
    expect($tier->feature('max_images_per_listing'))->toBe(5);
    expect($tier->feature('verified_badge'))->toBeTrue();
    expect($tier->feature('api_access'))->toBeTrue();
});

it('admin layout renders sidebar with all 4 sections', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $body = $this->actingAs($admin)->get(route('admin.index'))->assertOk()->getContent();

    expect($body)->toContain('Преглед');
    expect($body)->toContain('Агенции');
    expect($body)->toContain('Огласи');
    expect($body)->toContain('Планови');
    // Modern sidebar should NOT have the old top tab nav strip
    expect($body)->not->toContain('admin._nav');
});
