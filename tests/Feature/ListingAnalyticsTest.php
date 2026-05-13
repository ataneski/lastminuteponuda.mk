<?php

use App\Models\Inquiry;
use App\Models\Listing;
use App\Models\ListingView;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->owner = User::factory()->create([
        'subscription_tier' => 'pro',
        'subscription_until' => now()->addYear(),
    ]);
    $this->stranger = User::factory()->create();
    $this->listing = Listing::factory()->for($this->owner)->create();
});

it('redirects guests away from per-listing analytics', function () {
    $this->get(route('listings.analytics', $this->listing))->assertRedirect('/login');
});

it('forbids non-owners from per-listing analytics', function () {
    $this->actingAs($this->stranger)
        ->get(route('listings.analytics', $this->listing))
        ->assertForbidden();
});

it('shows the page to the owner', function () {
    $this->actingAs($this->owner)
        ->get(route('listings.analytics', $this->listing))
        ->assertOk()
        ->assertSee('Аналитика на оглас')
        ->assertSee($this->listing->title);
});

it('shows free upgrade prompt to free-tier owners', function () {
    $freeOwner = User::factory()->create();
    $listing = Listing::factory()->for($freeOwner)->create([
        'views_count' => 42,
    ]);

    $this->actingAs($freeOwner)
        ->get(route('listings.analytics', $listing))
        ->assertOk()
        ->assertSee('не е дел од твојот план')
        ->assertSee('42'); // existing total views shown as preview
});

it('increments daily views row on first visit and bumps the count on second', function () {
    $today = now()->toDateString();

    $this->get(route('listings.show', $this->listing));
    $this->get(route('listings.show', $this->listing));
    $this->get(route('listings.show', $this->listing));

    $row = ListingView::where('listing_id', $this->listing->id)
        ->where('day', $today)
        ->first();

    expect($row)->not->toBeNull();
    expect($row->count)->toBe(3);
    expect($this->listing->fresh()->views_count)->toBe(3);
});

it('does not increment daily views for owner views', function () {
    $this->actingAs($this->owner)->get(route('listings.show', $this->listing));

    expect(ListingView::count())->toBe(0);
});

it('renders 30-day chart with views per day', function () {
    // Three days ago: 5 views
    ListingView::create([
        'listing_id' => $this->listing->id,
        'day' => now()->subDays(3)->toDateString(),
        'count' => 5,
    ]);
    // Yesterday: 12 views
    ListingView::create([
        'listing_id' => $this->listing->id,
        'day' => now()->subDay()->toDateString(),
        'count' => 12,
    ]);

    $this->actingAs($this->owner);

    Livewire::test('listing-analytics', ['listing' => $this->listing])
        ->assertSet('range', '30')
        ->assertSee('17')        // total views (5 + 12)
        ->assertSee('Прегледи во период');
});

it('switches the time range', function () {
    $this->actingAs($this->owner);

    Livewire::test('listing-analytics', ['listing' => $this->listing])
        ->call('setRange', '7')
        ->assertSet('range', '7')
        ->call('setRange', '90')
        ->assertSet('range', '90');
});

it('rejects unsupported range values', function () {
    $this->actingAs($this->owner);

    Livewire::test('listing-analytics', ['listing' => $this->listing])
        ->call('setRange', '999')
        ->assertSet('range', '30'); // unchanged
});

it('shows inquiries scoped to the time range', function () {
    $inRange = Inquiry::create([
        'listing_id' => $this->listing->id,
        'agency_id' => $this->owner->id,
        'sender_name' => 'In Range',
        'sender_email' => 'in@example.com',
        'body' => 'Recent inquiry',
    ]);
    $inRange->forceFill(['created_at' => now()->subDays(5)])->save();

    $outOfRange = Inquiry::create([
        'listing_id' => $this->listing->id,
        'agency_id' => $this->owner->id,
        'sender_name' => 'Out Of Range',
        'sender_email' => 'out@example.com',
        'body' => 'Old inquiry',
    ]);
    $outOfRange->forceFill(['created_at' => now()->subDays(60)])->save();

    $this->actingAs($this->owner);

    Livewire::test('listing-analytics', ['listing' => $this->listing])
        ->set('range', '30')
        ->assertSee('In Range')
        ->assertDontSee('Out Of Range');

    // 90-day window includes both
    Livewire::test('listing-analytics', ['listing' => $this->listing])
        ->set('range', '90')
        ->assertSee('In Range')
        ->assertSee('Out Of Range');
});

it('cascades daily view rows when listing is deleted', function () {
    ListingView::create([
        'listing_id' => $this->listing->id,
        'day' => now()->toDateString(),
        'count' => 5,
    ]);

    expect(ListingView::count())->toBe(1);

    $this->listing->delete();

    expect(ListingView::count())->toBe(0);
});

it('shows views and inquiries badges on /agencija/moi-oglasi', function () {
    $this->listing->update(['views_count' => 42]);
    foreach (range(1, 3) as $i) {
        Inquiry::create([
            'listing_id' => $this->listing->id,
            'agency_id' => $this->owner->id,
            'sender_name' => "Sender {$i}",
            'sender_email' => "s{$i}@example.com",
            'body' => 'Test inquiry',
        ]);
    }

    $this->actingAs($this->owner)
        ->get(route('listings.mine'))
        ->assertOk()
        ->assertSee('42')
        ->assertSee('3 прашања');
});
