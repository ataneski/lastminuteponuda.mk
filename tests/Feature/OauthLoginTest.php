<?php

use App\Models\User;
use Laravel\Socialite\Contracts\Provider as SocialiteProvider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

beforeEach(function () {
    config()->set('services.google.client_id', 'test-google-id');
    config()->set('services.google.client_secret', 'test-secret');
    config()->set('services.facebook.client_id', 'test-fb-id');
    config()->set('services.facebook.client_secret', 'test-fb-secret');
});

function fakeSocialUser(string $id, ?string $email, string $name, ?string $avatar = null): SocialiteUser
{
    $u = new SocialiteUser;
    $u->id = $id;
    $u->email = $email;
    $u->name = $name;
    $u->avatar = $avatar ?? 'https://example.com/avatar.png';

    return $u;
}

function mockSocialiteCallback(string $provider, SocialiteUser $user): void
{
    $mock = Mockery::mock(SocialiteProvider::class);
    $mock->shouldReceive('user')->andReturn($user);
    Socialite::shouldReceive('driver')->with($provider)->andReturn($mock);
}

it('redirects to the provider on /auth/{provider}/redirect', function () {
    Socialite::shouldReceive('driver')->with('google')->andReturnUsing(function () {
        $mock = Mockery::mock(SocialiteProvider::class);
        $mock->shouldReceive('redirect')->andReturn(redirect('https://accounts.google.com/oauth/test'));

        return $mock;
    });

    $this->get('/auth/google/redirect')->assertRedirect('https://accounts.google.com/oauth/test');
});

it('returns 404 for unsupported providers', function () {
    $this->get('/auth/twitter/redirect')->assertNotFound();
});

it('creates a customer on first OAuth callback and redirects to complete-profile', function () {
    mockSocialiteCallback('google', fakeSocialUser(
        id: 'g-12345',
        email: 'newcustomer@example.com',
        name: 'Иван Петров',
    ));

    $this->get('/auth/google/callback')
        ->assertRedirect(route('customer.complete-profile'));

    $u = User::where('email', 'newcustomer@example.com')->firstOrFail();
    expect($u->role)->toBe('customer');
    expect($u->provider)->toBe('google');
    expect($u->provider_id)->toBe('g-12345');
    expect($u->first_name)->toBe('Иван');
    expect($u->last_name)->toBe('Петров');
    expect($u->phone)->toBeNull();
    expect($u->email_verified_at)->not->toBeNull();
    $this->assertAuthenticated();
});

it('logs in an existing OAuth-linked user without creating a duplicate', function () {
    $existing = User::factory()->create([
        'role' => 'customer',
        'provider' => 'google',
        'provider_id' => 'g-99',
        'email' => 'returning@example.com',
        'phone' => '+38970000000',
    ]);

    mockSocialiteCallback('google', fakeSocialUser(
        id: 'g-99',
        email: 'returning@example.com',
        name: 'Returning User',
    ));

    $this->get('/auth/google/callback')
        ->assertRedirect('/'); // has phone → no complete-profile detour

    expect(User::count())->toBe(1);
    $this->assertAuthenticatedAs($existing);
});

it('links provider to an existing email-only account', function () {
    $existing = User::factory()->create([
        'role' => 'customer',
        'email' => 'existing@example.com',
        'provider' => null,
        'provider_id' => null,
        'phone' => '+38970111111',
    ]);

    mockSocialiteCallback('facebook', fakeSocialUser(
        id: 'fb-7',
        email: 'existing@example.com',
        name: 'Some Name',
    ));

    $this->get('/auth/facebook/callback')
        ->assertRedirect('/');

    $existing->refresh();
    expect($existing->provider)->toBe('facebook');
    expect($existing->provider_id)->toBe('fb-7');
    expect($existing->email_verified_at)->not->toBeNull();
});

it('redirects to login with an error if Socialite throws', function () {
    Socialite::shouldReceive('driver')->with('google')->andReturnUsing(function () {
        $mock = Mockery::mock(SocialiteProvider::class);
        $mock->shouldReceive('user')->andThrow(new \RuntimeException('token failed'));

        return $mock;
    });

    $this->get('/auth/google/callback')
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['email']);

    $this->assertGuest();
});

it('shows complete-profile form for OAuth users with no phone', function () {
    $u = User::factory()->create([
        'role' => 'customer',
        'phone' => null,
        'first_name' => 'Иван',
    ]);

    $this->actingAs($u)
        ->get('/dopolni-profil')
        ->assertOk()
        ->assertSee('Уште еден чекор')
        ->assertSee('Иван');
});

it('skips complete-profile when phone already set', function () {
    $u = User::factory()->create([
        'role' => 'customer',
        'phone' => '+38970000000',
    ]);

    $this->actingAs($u)
        ->get('/dopolni-profil')
        ->assertRedirect('/');
});

it('persists phone + marketing consent from complete-profile and stamps timestamp', function () {
    $u = User::factory()->create([
        'role' => 'customer',
        'phone' => null,
        'marketing_consent' => false,
        'marketing_consent_at' => null,
    ]);

    $this->actingAs($u)
        ->post('/dopolni-profil', [
            'first_name' => 'Иван',
            'last_name' => 'Петров',
            'phone' => '+38970222333',
            'marketing_consent' => '1',
        ])
        ->assertRedirect('/');

    $u->refresh();
    expect($u->phone)->toBe('+38970222333');
    expect($u->marketing_consent)->toBeTrue();
    expect($u->marketing_consent_at)->not->toBeNull();
    expect($u->name)->toBe('Иван Петров');
});

it('shows OAuth buttons on /register only when configured', function () {
    $this->get('/register')
        ->assertOk()
        ->assertSee('Продолжи со Google')
        ->assertSee('Продолжи со Facebook');

    config()->set('services.google.client_id', null);
    config()->set('services.facebook.client_id', null);

    $this->get('/register')
        ->assertOk()
        ->assertDontSee('Продолжи со Google')
        ->assertDontSee('Продолжи со Facebook');
});

it('shows OAuth buttons on /login when configured', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('Продолжи со Google');
});
