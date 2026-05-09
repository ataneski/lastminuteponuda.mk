<?php

use App\Models\Listing;
use App\Models\User;
use Livewire\Livewire;

it('hides expired listings from the public index', function () {
    Listing::factory()->create(['title' => 'Активна', 'expires_at' => now()->addDay()]);
    Listing::factory()->create(['title' => 'Истечена', 'expires_at' => now()->subDay()]);
    Listing::factory()->create(['title' => 'Без рок', 'expires_at' => null]);

    $this->get('/oglasi')
        ->assertOk()
        ->assertSee('Активна')
        ->assertSee('Без рок')
        ->assertDontSee('Истечена');
});

it('hides expired listings from the home page', function () {
    Listing::factory()->create(['title' => 'Активен дом', 'expires_at' => now()->addDay()]);
    Listing::factory()->create(['title' => 'Истечен дом', 'expires_at' => now()->subDay()]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Активен дом')
        ->assertDontSee('Истечен дом');
});

it('returns 404 for expired listing detail to anonymous visitors', function () {
    $listing = Listing::factory()->create(['expires_at' => now()->subDay()]);

    $this->get(route('listings.show', $listing))->assertNotFound();
});

it('lets the owner see their own expired listing', function () {
    $owner = User::factory()->create();
    $listing = Listing::factory()->for($owner)->create([
        'title' => 'Мој истечен',
        'expires_at' => now()->subDay(),
    ]);

    $this->actingAs($owner)
        ->get(route('listings.show', $listing))
        ->assertOk()
        ->assertSee('Мој истечен');
});

it('allows agency to set expires_at when creating', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('listing-form')
        ->set([
            'agency_name' => 'A',
            'agency_contact' => 'a@a.mk',
            'title' => 'Тест истек',
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
            'expires_at' => now()->addDays(3)->format('Y-m-d\TH:i'),
        ])
        ->call('save')
        ->assertRedirect();

    $listing = Listing::first();
    expect($listing->expires_at)->not->toBeNull();
    expect($listing->expires_at->isFuture())->toBeTrue();
});

it('rejects expires_at in the past', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('listing-form')
        ->set('expires_at', now()->subWeek()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasErrors(['expires_at']);
});
