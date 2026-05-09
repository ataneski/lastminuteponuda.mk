<?php

use App\Models\Listing;
use App\Models\User;

it('drafts are not in the active scope', function () {
    Listing::factory()->create(['title' => 'Published one']);
    Listing::factory()->draft()->create(['title' => 'Draft one']);

    $titles = Listing::active()->pluck('title')->all();

    expect($titles)->toContain('Published one');
    expect($titles)->not->toContain('Draft one');
});

it('drafts do not appear on the public index', function () {
    Listing::factory()->draft()->create(['title' => 'Hidden draft']);

    $this->get('/oglasi')
        ->assertOk()
        ->assertDontSee('Hidden draft');
});

it('drafts return 404 for anonymous visitors on the detail page', function () {
    $listing = Listing::factory()->draft()->create();

    $this->get(route('listings.show', $listing))->assertNotFound();
});

it('owner can view their own draft', function () {
    $owner = User::factory()->create();
    $listing = Listing::factory()->draft()->for($owner)->create([
        'title' => 'My draft',
    ]);

    $this->actingAs($owner)
        ->get(route('listings.show', $listing))
        ->assertOk()
        ->assertSee('My draft');
});

it('publish() sets published_at and is idempotent', function () {
    $listing = Listing::factory()->draft()->create();
    expect($listing->isDraft())->toBeTrue();

    $listing->publish();
    $listing->refresh();

    expect($listing->isDraft())->toBeFalse();
    expect($listing->published_at)->not->toBeNull();

    // calling again should not bump the timestamp
    $first = $listing->published_at;
    $listing->publish();
    expect($listing->fresh()->published_at->eq($first))->toBeTrue();
});

it('draft scope returns only drafts', function () {
    Listing::factory()->create();
    Listing::factory()->create();
    Listing::factory()->draft()->create();
    Listing::factory()->draft()->create();

    expect(Listing::draft()->count())->toBe(2);
    expect(Listing::active()->count())->toBe(2);
});
