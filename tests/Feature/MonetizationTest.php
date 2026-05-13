<?php

use App\Models\Inquiry;
use App\Models\Listing;
use App\Models\User;
use Livewire\Livewire;

function listingFormData(): array
{
    return [
        'agency_name' => 'A',
        'agency_contact' => 'a@a.mk',
        'title' => 'Tier тест',
        'destination' => 'Анталија',
        'country' => 'Турција',
        'hotel_name' => 'X',
        'hotel_stars' => 4,
        'board_type' => 'allInclusive',
        'transport' => 'plane',
        'departure_date' => now()->addWeek()->toDateString(),
        'return_date' => now()->addWeeks(2)->toDateString(),
        'nights' => 7,
        'price_per_person' => 500,
        'currency' => 'EUR',
        'available_seats' => 2,
        'description' => 'Опис кој е доволно долг за валидација.',
    ];
}

// Tier resolution + cap

it('defaults users to free tier', function () {
    $u = User::factory()->create();
    expect($u->effectiveTier())->toBe('free');
    expect($u->isPro())->toBeFalse();
});

it('treats pro tier as effective when subscription_until is in the future', function () {
    $u = User::factory()->create([
        'subscription_tier' => 'pro',
        'subscription_until' => now()->addMonth(),
    ]);
    expect($u->effectiveTier())->toBe('pro');
    expect($u->isPro())->toBeTrue();
});

it('falls back to free when paid subscription_until is in the past', function () {
    $u = User::factory()->create([
        'subscription_tier' => 'pro',
        'subscription_until' => now()->subDay(),
    ]);
    expect($u->effectiveTier())->toBe('free');
    expect($u->isPro())->toBeFalse();
});

it('blocks free-tier users from creating a 4th active listing', function () {
    $u = User::factory()->create();
    Listing::factory()->count(3)->for($u)->create(['expires_at' => now()->addDays(30)]);

    expect($u->canCreateListing())->toBeFalse();

    $this->actingAs($u);
    Livewire::test('listing-form')
        ->set(listingFormData())
        ->call('save')
        ->assertHasErrors(['agency_name']);

    expect(Listing::count())->toBe(3);
});

it('allows pro users to create unlimited active listings', function () {
    $u = User::factory()->create([
        'subscription_tier' => 'pro',
        'subscription_until' => now()->addYear(),
    ]);
    Listing::factory()->count(5)->for($u)->create(['expires_at' => now()->addDays(30)]);

    expect($u->canCreateListing())->toBeTrue();
});

it('counts only active listings towards the free cap', function () {
    $u = User::factory()->create();
    Listing::factory()->for($u)->create(['expires_at' => now()->subDay()]);
    Listing::factory()->for($u)->create(['expires_at' => now()->subDays(2)]);
    Listing::factory()->for($u)->create(['expires_at' => now()->subDays(3)]);

    expect($u->activeListingCount())->toBe(0);
    expect($u->canCreateListing())->toBeTrue();
});

// Featured

it('shows featured badge on listings index for boosted listings', function () {
    Listing::factory()->create(['title' => 'Препорачаниот', 'featured_until' => now()->addDays(7)]);
    Listing::factory()->create(['title' => 'Обичен', 'featured_until' => null]);

    $this->get('/oglasi')
        ->assertOk()
        ->assertSee('Препорачано');
});

it('expired featured_until is no longer treated as featured', function () {
    $listing = Listing::factory()->create(['featured_until' => now()->subDay()]);
    expect($listing->isFeatured())->toBeFalse();
});

it('sorts featured listings before non-featured', function () {
    $old = Listing::factory()->create([
        'title' => 'Стариот без boost',
        'created_at' => now()->subDays(10),
        'featured_until' => null,
    ]);
    $featured = Listing::factory()->create([
        'title' => 'Нов со boost',
        'created_at' => now(),
        'featured_until' => now()->addDays(7),
    ]);

    $response = $this->get('/oglasi');
    $body = $response->getContent();
    $posOld = strpos($body, 'Стариот без boost');
    $posFeatured = strpos($body, 'Нов со boost');

    expect($posFeatured)->toBeLessThan($posOld);
});

// View counter + inquiry logging

it('increments views_count when a non-owner views the listing', function () {
    $listing = Listing::factory()->create(['views_count' => 0]);

    $this->get(route('listings.show', $listing));
    $this->get(route('listings.show', $listing));

    expect($listing->fresh()->views_count)->toBe(2);
});

it('does not count owner views', function () {
    $owner = User::factory()->create();
    $listing = Listing::factory()->for($owner)->create(['views_count' => 0]);

    $this->actingAs($owner)->get(route('listings.show', $listing));

    expect($listing->fresh()->views_count)->toBe(0);
});

it('logs an Inquiry row when the form is submitted', function () {
    \Illuminate\Support\Facades\Mail::fake();
    \Illuminate\Support\Facades\RateLimiter::clear('inquiry:127.0.0.1');
    $owner = User::factory()->create();
    $listing = Listing::factory()->for($owner)->create();

    Livewire::test('listing-inquiry', ['listing' => $listing])
        ->set('name', 'Иван')
        ->set('email', 'ivan@example.com')
        ->set('bodyMessage', 'Доволно долго прашање за тестирање на запис.')
        ->call('submit')
        ->assertSet('sent', true);

    expect(Inquiry::count())->toBe(1);
    $inq = Inquiry::first();
    expect($inq->agency_id)->toBe($owner->id);
    expect($inq->sender_name)->toBe('Иван');
});

// Analytics

it('redirects guests away from analytics', function () {
    $this->get(route('agency.analytics'))->assertRedirect('/login');
});

it('shows upgrade prompt to free users on analytics', function () {
    $u = User::factory()->create();
    $this->actingAs($u)
        ->get(route('agency.analytics'))
        ->assertOk()
        ->assertSee('не е дел од твојот план');
});

it('shows analytics dashboard to pro users', function () {
    $u = User::factory()->create([
        'subscription_tier' => 'pro',
        'subscription_until' => now()->addYear(),
    ]);

    $this->actingAs($u)
        ->get(route('agency.analytics'))
        ->assertOk()
        ->assertSee('Активни огласи')
        ->assertSee('Конверзија');
});

// Admin

it('returns 404 from admin pages for non-admin users', function () {
    $u = User::factory()->create(['is_admin' => false]);
    $this->actingAs($u)->get('/admin')->assertNotFound();
});

it('lets admins access the admin dashboard', function () {
    $u = User::factory()->create(['is_admin' => true]);
    $this->actingAs($u)
        ->get('/admin')
        ->assertOk()
        ->assertSee('Агенции');
});

it('admin can update a users tier', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $target = User::factory()->create();

    $this->actingAs($admin)
        ->patch(route('admin.users.update-tier', $target), [
            'subscription_tier' => 'pro',
            'subscription_until' => now()->addYear()->toDateString(),
        ])
        ->assertRedirect();

    expect($target->fresh()->effectiveTier())->toBe('pro');
});

it('admin can feature a listing', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $listing = Listing::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.listings.feature', $listing), ['days' => 14])
        ->assertRedirect();

    $listing->refresh();
    expect($listing->isFeatured())->toBeTrue();
    expect($listing->featured_until->diffInDays(now()))->toBeLessThan(15);
});

it('admin can unfeature a listing', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $listing = Listing::factory()->create(['featured_until' => now()->addDays(30)]);

    $this->actingAs($admin)
        ->delete(route('admin.listings.unfeature', $listing))
        ->assertRedirect();

    expect($listing->fresh()->isFeatured())->toBeFalse();
});

// Social card

it('serves a 1080x1080 PNG social card for a listing', function () {
    $listing = Listing::factory()->create();

    $response = $this->get(route('listings.social-card', $listing));

    $response->assertOk()
        ->assertHeader('Content-Type', 'image/png');

    $bytes = $response->getContent();
    $img = imagecreatefromstring($bytes);
    expect(imagesx($img))->toBe(1080);
    expect(imagesy($img))->toBe(1080);
});

it('returns 404 for social card of an expired listing to anonymous visitors', function () {
    $listing = Listing::factory()->create(['expires_at' => now()->subDay()]);
    $this->get(route('listings.social-card', $listing))->assertNotFound();
});

// Upgrade page + UTM

it('renders the upgrade plans page', function () {
    $this->get(route('upgrade'))
        ->assertOk()
        ->assertSee('Pro')
        ->assertSee('990 MKD')
        ->assertSee('Featured Boost');
});

it('UTM helper appends params correctly', function () {
    $url = \App\Support\Utm::tag('https://example.com/path', 'instagram', 'social', 'campaign_x');

    expect($url)->toContain('utm_source=instagram')
        ->toContain('utm_medium=social')
        ->toContain('utm_campaign=campaign_x');
});

it('UTM helper preserves an existing query string', function () {
    $url = \App\Support\Utm::tag('https://example.com/path?ref=1', 'facebook');
    expect($url)->toContain('ref=1')
        ->toContain('utm_source=facebook');
});
