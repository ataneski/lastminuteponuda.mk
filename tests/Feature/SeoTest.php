<?php

use App\Models\Listing;

it('serves a sitemap.xml with active listings', function () {
    $active = Listing::factory()->create(['expires_at' => now()->addDay()]);
    $expired = Listing::factory()->create(['expires_at' => now()->subDay()]);

    $response = $this->get('/sitemap.xml');

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/xml');

    $body = $response->getContent();
    expect($body)->toContain('<urlset')
        ->toContain(route('listings.show', $active))
        ->toContain(route('listings.index'))
        ->not->toContain(route('listings.show', $expired));
});

it('outputs canonical, og and twitter meta on home', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('rel="canonical"', false)
        ->assertSee('property="og:type"', false)
        ->assertSee('property="og:title"', false)
        ->assertSee('name="twitter:card"', false);
});

it('outputs JSON-LD TouristTrip schema on listing detail', function () {
    $listing = Listing::factory()->create([
        'title' => 'Анталија сончев одмор',
        'destination' => 'Анталија',
        'country' => 'Турција',
        'price_per_person' => 599,
        'currency' => 'EUR',
    ]);

    $response = $this->get(route('listings.show', $listing));

    $response->assertOk()
        ->assertSee('application/ld+json', false)
        ->assertSee('TouristTrip', false)
        ->assertSee('TravelAgency', false)
        ->assertSee('"price": 599', false)
        ->assertSee('"priceCurrency": "EUR"', false);
});

it('robots.txt mentions sitemap and disallows agency routes', function () {
    $body = file_get_contents(public_path('robots.txt'));

    expect($body)->toContain('Sitemap:')
        ->toContain('Disallow: /agencija/');
});
