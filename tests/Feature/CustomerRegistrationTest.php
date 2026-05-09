<?php

use App\Models\Listing;
use App\Models\User;

it('renders the customer registration page', function () {
    $this->get('/register')
        ->assertOk()
        ->assertSee('Регистрирај се')
        ->assertSee('Регистрирај агенција'); // link to agency variant
});

it('renders the agency registration page', function () {
    $this->get('/register-agencija')
        ->assertOk()
        ->assertSee('Регистрирај агенција');
});

it('creates a customer with role=customer + marketing flag', function () {
    $this->post('/register', [
        'first_name' => 'Иван',
        'last_name' => 'Петров',
        'phone' => '+38970111222',
        'email' => 'ivan@example.com',
        'password' => 'StrongPass1!',
        'password_confirmation' => 'StrongPass1!',
        'marketing_consent' => '1',
    ])->assertRedirect('/');

    $u = User::where('email', 'ivan@example.com')->firstOrFail();
    expect($u->role)->toBe('customer');
    expect($u->first_name)->toBe('Иван');
    expect($u->last_name)->toBe('Петров');
    expect($u->name)->toBe('Иван Петров');
    expect($u->phone)->toBe('+38970111222');
    expect($u->marketing_consent)->toBeTrue();
    expect($u->marketing_consent_at)->not->toBeNull();
    expect($u->slug)->toBeNull(); // customers have no public slug
    expect($u->isCustomer())->toBeTrue();
    expect($u->isAgency())->toBeFalse();
});

it('does not stamp marketing_consent_at when checkbox is unchecked', function () {
    $this->post('/register', [
        'first_name' => 'Петар',
        'last_name' => 'Петров',
        'phone' => '+38970111223',
        'email' => 'petar@example.com',
        'password' => 'StrongPass1!',
        'password_confirmation' => 'StrongPass1!',
    ])->assertRedirect('/');

    $u = User::where('email', 'petar@example.com')->firstOrFail();
    expect($u->marketing_consent)->toBeFalse();
    expect($u->marketing_consent_at)->toBeNull();
});

it('rejects customer registration with missing fields', function () {
    $this->post('/register', [
        'email' => 'incomplete@example.com',
    ])->assertSessionHasErrors(['first_name', 'last_name', 'phone', 'password']);
});

it('creates an agency with role=agency on the agency route', function () {
    $this->post('/register-agencija', [
        'name' => 'Балкан Травел',
        'email' => 'agency@example.com',
        'password' => 'StrongPass1!',
        'password_confirmation' => 'StrongPass1!',
    ])->assertRedirect(route('agency.profile.edit'));

    $u = User::where('email', 'agency@example.com')->firstOrFail();
    expect($u->role)->toBe('agency');
    expect($u->isAgency())->toBeTrue();
    expect($u->slug)->not->toBeNull();
});

it('login redirects an agency to listings.mine', function () {
    $u = User::factory()->create([
        'role' => 'agency',
        'email' => 'agency2@example.com',
        'password' => bcrypt('Password123!'),
    ]);

    $this->post('/login', [
        'email' => 'agency2@example.com',
        'password' => 'Password123!',
    ])->assertRedirect(route('listings.mine'));
});

it('login redirects a customer to home', function () {
    $u = User::factory()->create([
        'role' => 'customer',
        'email' => 'customer@example.com',
        'password' => bcrypt('Password123!'),
    ]);

    $this->post('/login', [
        'email' => 'customer@example.com',
        'password' => 'Password123!',
    ])->assertRedirect('/');
});

// -- Privacy gates --

it('hides the listing price from guests on the index', function () {
    Listing::factory()->create(['title' => 'Видлив наслов', 'price_per_person' => 599, 'currency' => 'EUR']);

    $body = $this->get('/oglasi')->assertOk()->getContent();

    expect($body)->toContain('Видлив наслов');
    expect($body)->toContain('Логирај се за цена');
    expect($body)->not->toContain('599 EUR');
});

it('shows the listing price to authenticated users', function () {
    $u = User::factory()->create(['role' => 'customer']);
    Listing::factory()->create(['title' => 'For customer', 'price_per_person' => 599, 'currency' => 'EUR']);

    $body = $this->actingAs($u)->get('/oglasi')->assertOk()->getContent();

    expect($body)->toContain('599');
    expect($body)->not->toContain('Логирај се за цена');
});

it('shows the registration CTA on listing detail for guests', function () {
    $listing = Listing::factory()->create();

    $this->get(route('listings.show', $listing))
        ->assertOk()
        ->assertSee('Регистрирај се за цена')
        ->assertSee('видливи само за регистрирани корисници');
});

it('hides agency_contact from guests on listing detail', function () {
    $listing = Listing::factory()->create([
        'agency_contact' => '+389 70 111 222',
    ]);

    $this->get(route('listings.show', $listing))
        ->assertOk()
        ->assertDontSee('+389 70 111 222');
});

it('shows agency_contact to authenticated customers', function () {
    $u = User::factory()->create(['role' => 'customer']);
    $listing = Listing::factory()->create([
        'agency_contact' => '+389 70 999 888',
    ]);

    $this->actingAs($u)
        ->get(route('listings.show', $listing))
        ->assertSee('+389 70 999 888');
});

it('replaces inquiry form with register CTA for guests', function () {
    $listing = Listing::factory()->create();

    $body = $this->get(route('listings.show', $listing))->assertOk()->getContent();

    expect($body)->toContain('испратите прашање, потребно е да сте регистриран');
    // The actual Livewire form is not rendered for guests:
    expect($body)->not->toContain('wire:submit="submit"');
});

// -- Customer profile --

it('redirects guests away from /moj-profil', function () {
    $this->get('/moj-profil')->assertRedirect('/login');
});

it('lets a customer view their profile', function () {
    $u = User::factory()->create(['role' => 'customer', 'first_name' => 'Иван']);

    $this->actingAs($u)
        ->get('/moj-profil')
        ->assertOk()
        ->assertSee('Мој профил')
        ->assertSee('Иван');
});

it('updates customer profile + flips marketing_consent_at on enable', function () {
    $u = User::factory()->create([
        'role' => 'customer',
        'first_name' => 'X',
        'last_name' => 'Y',
        'marketing_consent' => false,
        'marketing_consent_at' => null,
    ]);

    $this->actingAs($u)
        ->patch('/moj-profil', [
            'first_name' => 'Иван',
            'last_name' => 'Петров',
            'phone' => '+38970000000',
            'email' => $u->email,
            'marketing_consent' => '1',
        ])
        ->assertRedirect();

    $u->refresh();
    expect($u->first_name)->toBe('Иван');
    expect($u->name)->toBe('Иван Петров');
    expect($u->marketing_consent)->toBeTrue();
    expect($u->marketing_consent_at)->not->toBeNull();
});
