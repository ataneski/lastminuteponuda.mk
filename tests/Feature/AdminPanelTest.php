<?php

use App\Models\Listing;
use App\Models\Tier;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'email' => 'root@lmp.mk',
    ]);
});

// ─── Role + nav ─────────────────────────────────────────────────

it('isAdmin returns true for role=admin even without legacy is_admin', function () {
    $u = User::factory()->create(['role' => 'admin', 'is_admin' => false]);
    expect($u->isAdmin())->toBeTrue();
});

it('isAdmin still respects legacy is_admin flag', function () {
    $u = User::factory()->create(['role' => 'agency', 'is_admin' => true]);
    expect($u->isAdmin())->toBeTrue();
});

it('admin sees the admin nav and not the agency nav', function () {
    $body = $this->actingAs($this->admin)->get('/')->assertOk()->getContent();
    expect($body)->toContain('Admin');
    expect($body)->toContain('Агенции');
    expect($body)->toContain('Tiers');
    // Agency-only items should NOT appear
    expect($body)->not->toContain('Мои огласи');
    expect($body)->not->toContain('Нов оглас (AI)');
});

it('agency sees agency nav and not admin nav', function () {
    $agency = User::factory()->create(['role' => 'agency', 'is_admin' => false]);
    $body = $this->actingAs($agency)->get('/')->assertOk()->getContent();
    expect($body)->toContain('Мои огласи');
    expect($body)->not->toContain('Tiers'); // admin-only nav item
});

// ─── Login redirects ────────────────────────────────────────────

it('admin login lands on /admin', function () {
    $u = User::factory()->create([
        'role' => 'admin',
        'email' => 'newadmin@lmp.mk',
        'password' => 'password',
    ]);

    $this->post('/login', [
        'email' => 'newadmin@lmp.mk',
        'password' => 'password',
    ])->assertRedirect(route('admin.index'));
});

// ─── Suspension ─────────────────────────────────────────────────

it('suspended user cannot log in', function () {
    $u = User::factory()->create([
        'email' => 'suspended@lmp.mk',
        'password' => 'password',
        'suspended_at' => now(),
        'suspension_reason' => 'spam',
    ]);

    $this->post('/login', [
        'email' => 'suspended@lmp.mk',
        'password' => 'password',
    ])
        ->assertSessionHasErrors(['email']);

    $this->assertGuest();
});

it('suspends and unsuspends an agency from admin', function () {
    $agency = User::factory()->create(['role' => 'agency']);

    $this->actingAs($this->admin)
        ->post(route('admin.users.suspend', $agency), ['reason' => 'test'])
        ->assertRedirect();

    expect($agency->fresh()->isSuspended())->toBeTrue();
    expect($agency->fresh()->suspension_reason)->toBe('test');

    $this->actingAs($this->admin)
        ->delete(route('admin.users.unsuspend', $agency))
        ->assertRedirect();

    expect($agency->fresh()->isSuspended())->toBeFalse();
});

it('suspended listings are hidden from public', function () {
    $listing = Listing::factory()->create();
    $listing->suspend('spam');

    $this->get(route('listings.show', $listing))->assertNotFound();
    $this->get('/oglasi')->assertDontSee($listing->title);
});

it('owner can still see their own suspended listing', function () {
    $owner = User::factory()->create();
    $listing = Listing::factory()->for($owner)->create(['title' => 'My suspended']);
    $listing->suspend();

    $this->actingAs($owner)
        ->get(route('listings.show', $listing))
        ->assertOk()
        ->assertSee('My suspended');
});

// ─── Admin CRUD on agencies ─────────────────────────────────────

it('admin can create an agency manually', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('admin.agencies.store'), [
            'name' => 'Нова Агенција',
            'email' => 'newagency@lmp.mk',
            'phone' => '+38970000000',
            'subscription_tier' => 'pro',
            'password' => 'SecurePass1!',
        ])
        ->assertRedirect(route('admin.agencies'));

    $created = User::where('email', 'newagency@lmp.mk')->firstOrFail();
    expect($created->role)->toBe('agency');
    expect($created->subscription_tier)->toBe('pro');
    expect($created->email_verified_at)->not->toBeNull();
});

it('admin can delete an agency', function () {
    $target = User::factory()->create(['role' => 'agency']);

    $this->actingAs($this->admin)
        ->delete(route('admin.users.destroy', $target))
        ->assertRedirect(route('admin.agencies'));

    expect(User::find($target->id))->toBeNull();
});

it('admin cannot delete themselves', function () {
    $this->actingAs($this->admin)
        ->delete(route('admin.users.destroy', $this->admin))
        ->assertStatus(422);

    expect(User::find($this->admin->id))->not->toBeNull();
});

// ─── Admin CRUD on listings ─────────────────────────────────────

it('admin can suspend and delete a listing', function () {
    $listing = Listing::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.listings.suspend', $listing), ['reason' => 'inappropriate'])
        ->assertRedirect();
    expect($listing->fresh()->isSuspended())->toBeTrue();

    $this->actingAs($this->admin)
        ->delete(route('admin.listings.destroy', $listing))
        ->assertRedirect(route('admin.listings'));
    expect(Listing::find($listing->id))->toBeNull();
});

// ─── Tiers CRUD ─────────────────────────────────────────────────

it('seeds three default tiers via migration', function () {
    expect(Tier::count())->toBeGreaterThanOrEqual(3);
    expect(Tier::where('key', 'free')->exists())->toBeTrue();
    expect(Tier::where('key', 'pro')->exists())->toBeTrue();
    expect(Tier::where('key', 'premium')->exists())->toBeTrue();
});

it('pro tier has unlimited active listings (max_active_listings === null)', function () {
    $pro = Tier::where('key', 'pro')->firstOrFail();
    expect($pro->feature('max_active_listings'))->toBeNull();
});

it('admin can create a new tier with feature toggles', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.tiers.store'), [
            'key' => 'gold',
            'name' => 'Gold',
            'monthly_price' => 4990,
            'currency' => 'MKD',
            'active' => '1',
            'sort_order' => 30,
            'features' => [
                'max_active_listings' => '',           // empty → null = unlimited
                'featured_boosts_per_month' => '10',
                'analytics_enabled' => '1',
                'social_posts_per_month' => '8',
                'verified_badge' => '1',
                'whatsapp_intake' => '1',
                'ai_wizard' => '1',
                'custom_branding' => '1',
            ],
        ])
        ->assertRedirect(route('admin.tiers'));

    $gold = Tier::where('key', 'gold')->firstOrFail();
    expect($gold->feature('max_active_listings'))->toBeNull();
    expect($gold->feature('featured_boosts_per_month'))->toBe(10);
    expect($gold->feature('analytics_enabled'))->toBeTrue();
});

it('admin can update an existing tier', function () {
    $this->actingAs($this->admin)
        ->patch(route('admin.tiers.update', 'pro'), [
            'key' => 'pro',
            'name' => 'Pro+',
            'monthly_price' => 1490,
            'currency' => 'MKD',
            'active' => '1',
            'sort_order' => 10,
            'features' => [
                'max_active_listings' => '20',
                'featured_boosts_per_month' => '2',
                'analytics_enabled' => '1',
                'social_posts_per_month' => '1',
                'verified_badge' => '0',
                'whatsapp_intake' => '1',
                'ai_wizard' => '1',
                'custom_branding' => '1',
            ],
        ])
        ->assertRedirect(route('admin.tiers'));

    $pro = Tier::where('key', 'pro')->firstOrFail();
    expect($pro->name)->toBe('Pro+');
    expect($pro->monthly_price)->toBe(1490);
    expect($pro->feature('max_active_listings'))->toBe(20);
});

it('cannot delete a tier with assigned users', function () {
    User::factory()->create(['subscription_tier' => 'pro']);

    $this->actingAs($this->admin)
        ->delete(route('admin.tiers.destroy', 'pro'))
        ->assertRedirect();

    expect(Tier::where('key', 'pro')->exists())->toBeTrue();
});

it('canCreateListing reflects updated tier cap', function () {
    $agency = User::factory()->create([
        'subscription_tier' => 'pro',
        'subscription_until' => now()->addYear(),
    ]);
    Listing::factory()->count(5)->for($agency)->create(['expires_at' => now()->addDays(30)]);

    // Pro tier default = unlimited
    expect($agency->canCreateListing())->toBeTrue();

    // Admin lowers Pro to cap=3 → agency now blocked
    Tier::where('key', 'pro')->update([
        'features' => json_encode(array_merge(
            Tier::where('key', 'pro')->first()->features,
            ['max_active_listings' => 3]
        )),
    ]);

    expect($agency->fresh()->canCreateListing())->toBeFalse();
});

it('non-admin cannot access admin tier routes', function () {
    $agency = User::factory()->create(['role' => 'agency', 'is_admin' => false]);

    $this->actingAs($agency)->get(route('admin.tiers'))->assertNotFound();
});
