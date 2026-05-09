<?php

use App\Models\Listing;
use App\Models\User;

it('renders the home page with latest listings', function () {
    $listing = Listing::factory()->create([
        'title' => 'Тест понуда Анталија',
    ]);

    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('lastminuteponuda')
        ->assertSee('Тест понуда Анталија');
});

it('renders the listings index page', function () {
    Listing::factory()->count(3)->create();

    $this->get('/oglasi')->assertOk();
});

it('renders a listing detail page', function () {
    $listing = Listing::factory()->create([
        'title' => 'Детали тест',
        'description' => 'Опис на детал тестот за оваа понуда.',
    ]);

    $this->get("/oglasi/{$listing->id}")
        ->assertOk()
        ->assertSee('Детали тест')
        ->assertSee($listing->hotel_name);
});

it('returns 404 for missing listing', function () {
    $this->get('/oglasi/9999')->assertNotFound();
});

it('redirects guests away from agency create page', function () {
    $this->get('/agencija/nov-oglas')
        ->assertRedirect('/login');
});

it('allows authenticated users to view the create page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/agencija/nov-oglas')
        ->assertOk()
        ->assertSee('Поставете нов last minute');
});

it('redirects guests from my listings', function () {
    $this->get('/agencija/moi-oglasi')->assertRedirect('/login');
});

it('shows only the authenticated users listings', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    Listing::factory()->for($alice)->create(['title' => 'Alice оглас']);
    Listing::factory()->for($bob)->create(['title' => 'Bob оглас']);

    $this->actingAs($alice)
        ->get('/agencija/moi-oglasi')
        ->assertOk()
        ->assertSee('Alice оглас')
        ->assertDontSee('Bob оглас');
});
