<?php

use App\Models\Listing;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
});

function validFormData(): array
{
    return [
        'agency_name' => 'Балкан Травел',
        'agency_contact' => '+389 70 123 456',
        'title' => '7 ноќи Анталија — All Inclusive',
        'destination' => 'Анталија',
        'country' => 'Турција',
        'hotel_name' => 'Royal Seginus',
        'hotel_stars' => 5,
        'board_type' => 'allInclusive',
        'transport' => 'plane',
        'departure_date' => now()->addWeek()->toDateString(),
        'return_date' => now()->addWeek()->addDays(7)->toDateString(),
        'nights' => 7,
        'price_per_person' => 599,
        'currency' => 'EUR',
        'available_seats' => 4,
        'description' => 'Last minute понуда за лето во Анталија. Луксузен 5* хотел.',
    ];
}

it('creates a listing for the authenticated user', function () {
    $this->actingAs($this->user);

    Livewire::test('listing-form')
        ->set(validFormData())
        ->set('features', ['Базен', 'Wi-Fi'])
        ->call('save')
        ->assertRedirect();

    expect(Listing::count())->toBe(1);
    $listing = Listing::first();
    expect($listing->user_id)->toBe($this->user->id);
    expect($listing->features)->toBe(['Базен', 'Wi-Fi']);
    expect($listing->title)->toBe('7 ноќи Анталија — All Inclusive');
});

it('rejects missing required fields', function () {
    $this->actingAs($this->user);

    Livewire::test('listing-form')
        ->set('agency_name', '')
        ->call('save')
        ->assertHasErrors([
            'agency_name' => 'required',
            'title' => 'required',
            'destination' => 'required',
        ]);
});

it('rejects invalid board type', function () {
    $this->actingAs($this->user);

    Livewire::test('listing-form')
        ->set(validFormData())
        ->set('board_type', 'fake-board')
        ->call('save')
        ->assertHasErrors(['board_type']);
});

it('rejects return date before departure', function () {
    $this->actingAs($this->user);

    Livewire::test('listing-form')
        ->set(validFormData())
        ->set('return_date', now()->subDay()->toDateString())
        ->call('save')
        ->assertHasErrors(['return_date']);
});

it('rejects departure date in the past', function () {
    $this->actingAs($this->user);

    Livewire::test('listing-form')
        ->set(validFormData())
        ->set('departure_date', now()->subWeek()->toDateString())
        ->call('save')
        ->assertHasErrors(['departure_date']);
});

it('rejects negative price', function () {
    $this->actingAs($this->user);

    Livewire::test('listing-form')
        ->set(validFormData())
        ->set('price_per_person', -10)
        ->call('save')
        ->assertHasErrors(['price_per_person']);
});

it('toggles a feature on and off', function () {
    $this->actingAs($this->user);

    Livewire::test('listing-form')
        ->call('toggleFeature', 'Базен')
        ->assertSet('features', ['Базен'])
        ->call('toggleFeature', 'Базен')
        ->assertSet('features', []);
});

it('adds a custom feature and skips duplicates', function () {
    $this->actingAs($this->user);

    Livewire::test('listing-form')
        ->set('custom_feature', 'Тенис терен')
        ->call('addCustomFeature')
        ->assertSet('features', ['Тенис терен'])
        ->assertSet('custom_feature', '')
        ->set('custom_feature', 'Тенис терен')
        ->call('addCustomFeature')
        ->assertSet('features', ['Тенис терен']);
});

it('uploads an image and stores its public URL', function () {
    Storage::fake('public');
    $this->actingAs($this->user);

    $file = UploadedFile::fake()->image('hotel.jpg', 800, 600);

    Livewire::test('listing-form')
        ->set(validFormData())
        ->set('image_file', $file)
        ->call('save')
        ->assertRedirect();

    $listing = Listing::first();
    expect($listing->image_url)->toContain('/storage/listings/');
    Storage::disk('public')->assertExists(
        str_replace('/storage/', '', $listing->image_url)
    );
});

it('rejects oversized image upload', function () {
    Storage::fake('public');
    $this->actingAs($this->user);

    $file = UploadedFile::fake()->image('huge.jpg')->size(5000);

    Livewire::test('listing-form')
        ->set(validFormData())
        ->set('image_file', $file)
        ->call('save')
        ->assertHasErrors(['image_file']);
});
