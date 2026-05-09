<?php

use App\Models\Listing;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('auto-generates a unique slug on user creation', function () {
    $a = User::factory()->create(['name' => 'Балкан Травел']);
    $b = User::factory()->create(['name' => 'Балкан Травел']);

    expect($a->slug)->not->toBeEmpty();
    expect($b->slug)->not->toBe($a->slug);
});

it('falls back to "agencija" when slug source is reserved or empty', function () {
    $u = User::factory()->create(['name' => '!!!']);

    expect($u->slug)->toStartWith('agencija');
});

it('redirects guests away from profile edit', function () {
    $this->get(route('agency.profile.edit'))->assertRedirect('/login');
});

it('lets authenticated users open profile edit', function () {
    $u = User::factory()->create();

    $this->actingAs($u)
        ->get(route('agency.profile.edit'))
        ->assertOk()
        ->assertSee('Профил на агенција');
});

it('shows public agency page with active listings', function () {
    $u = User::factory()->create([
        'name' => 'Балкан Травел',
        'display_name' => 'Балкан Травел DOO',
        'tagline' => 'Дестинации од 2010',
    ]);
    Listing::factory()->for($u)->create(['title' => 'Активен оглас', 'expires_at' => now()->addDay()]);
    Listing::factory()->for($u)->create(['title' => 'Истечен оглас', 'expires_at' => now()->subDay()]);

    $this->get(route('agency.show', $u))
        ->assertOk()
        ->assertSee('Балкан Травел DOO')
        ->assertSee('Дестинации од 2010')
        ->assertSee('Активен оглас')
        ->assertDontSee('Истечен оглас');
});

it('applies the accent color to the public agency page', function () {
    $u = User::factory()->create(['accent_color' => '#ff6600']);

    $this->get(route('agency.show', $u))
        ->assertOk()
        ->assertSee('background-color: #ff6600', false);
});

it('outputs TravelAgency JSON-LD on agency page', function () {
    $u = User::factory()->create(['display_name' => 'JSON LD Travel']);

    $this->get(route('agency.show', $u))
        ->assertOk()
        ->assertSee('TravelAgency', false)
        ->assertSee('JSON LD Travel');
});

it('updates profile fields via the Livewire form', function () {
    Storage::fake('public');
    $u = User::factory()->create();
    $this->actingAs($u);

    Livewire::test('agency-profile-form')
        ->set('display_name', 'Балкан Травел')
        ->set('tagline', 'Слоганот')
        ->set('description', 'Многу долг опис')
        ->set('phone', '+389 70 111 222')
        ->set('website', 'https://balkan-travel.mk')
        ->set('accent_color', '#ff0066')
        ->call('save')
        ->assertRedirect();

    $u->refresh();
    expect($u->display_name)->toBe('Балкан Травел');
    expect($u->tagline)->toBe('Слоганот');
    expect($u->accent_color)->toBe('#ff0066');
    expect($u->phone)->toBe('+389 70 111 222');
});

it('uploads a logo and stores its public URL', function () {
    Storage::fake('public');
    $u = User::factory()->create();
    $this->actingAs($u);

    Livewire::test('agency-profile-form')
        ->set('display_name', 'X')
        ->set('logo_file', UploadedFile::fake()->image('logo.png', 200, 200))
        ->call('save')
        ->assertRedirect();

    $u->refresh();
    expect($u->logo_url)->toContain('/storage/agency-logos/');
    Storage::disk('public')->assertExists(str_replace('/storage/', '', $u->logo_url));
});

it('rejects an invalid hex accent color', function () {
    $u = User::factory()->create();
    $this->actingAs($u);

    Livewire::test('agency-profile-form')
        ->set('accent_color', 'not-a-color')
        ->call('save')
        ->assertHasErrors(['accent_color']);
});

it('rejects a reserved slug', function () {
    $u = User::factory()->create();
    $this->actingAs($u);

    Livewire::test('agency-profile-form')
        ->set('slug_input', 'admin')
        ->call('save')
        ->assertHasErrors(['slug_input']);
});

it('reserved /agencija/* paths take precedence over slug catch-all', function () {
    $u = User::factory()->create();
    $this->actingAs($u);

    // Specific routes should still match.
    $this->get('/agencija/nov-oglas')->assertOk();
    $this->get('/agencija/moi-oglasi')->assertOk();
    $this->get('/agencija/profil')->assertOk();
});
