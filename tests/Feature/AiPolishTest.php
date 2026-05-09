<?php

use App\Models\Listing;
use App\Models\User;

it('counts recent drafts toward the free tier cap', function () {
    $user = User::factory()->create(); // free tier by default
    Listing::factory()->for($user)->create();
    Listing::factory()->draft()->for($user)->create(['created_at' => now()]);
    Listing::factory()->draft()->for($user)->create(['created_at' => now()]);

    // 1 active + 2 recent drafts = 3 → at cap
    expect($user->canCreateListing())->toBeFalse();
});

it('does not count drafts older than 24h toward the cap', function () {
    $user = User::factory()->create();
    Listing::factory()->draft()->for($user)->create(['created_at' => now()->subDays(2)]);
    Listing::factory()->draft()->for($user)->create(['created_at' => now()->subDays(3)]);

    // Two drafts but both > 24h old → don't count
    expect($user->canCreateListing())->toBeTrue();
});

it('purge command deletes drafts older than --days', function () {
    Listing::factory()->draft()->create(['created_at' => now()->subDays(8), 'title' => 'old draft']);
    Listing::factory()->draft()->create(['created_at' => now()->subDays(2), 'title' => 'recent draft']);
    Listing::factory()->create(['created_at' => now()->subDays(8), 'title' => 'published']);

    $this->artisan('listings:purge-stale-drafts')
        ->assertSuccessful();

    expect(Listing::pluck('title')->all())->not->toContain('old draft');
    expect(Listing::pluck('title')->all())->toContain('recent draft');
    expect(Listing::pluck('title')->all())->toContain('published');
});

it('purge command honors a custom --days value', function () {
    Listing::factory()->draft()->create(['created_at' => now()->subDays(3)]);

    $this->artisan('listings:purge-stale-drafts', ['--days' => 2])
        ->assertSuccessful();

    expect(Listing::draft()->count())->toBe(0);
});

it('purge command rejects --days < 1', function () {
    $this->artisan('listings:purge-stale-drafts', ['--days' => 0])
        ->assertFailed();
});

it('dry-run refuses to run in production', function () {
    app()['env'] = 'production';
    $listing = Listing::factory()->create();

    try {
        $this->artisan('ai:dry-run-listing', ['id' => $listing->id])
            ->assertFailed();
    } finally {
        app()['env'] = 'testing';
    }
});
