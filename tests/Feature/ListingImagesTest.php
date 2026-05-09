<?php

use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
    $this->owner = User::factory()->create();
});

function baseFormData(): array
{
    return [
        'agency_name' => 'A',
        'agency_contact' => 'a@a.mk',
        'title' => 'Multi image test',
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
        'description' => 'Опис кој е доволно долг за валидација на формата.',
    ];
}

it('creates a listing with multiple images', function () {
    $this->actingAs($this->owner);

    $files = [
        UploadedFile::fake()->image('a.jpg', 800, 600),
        UploadedFile::fake()->image('b.jpg', 800, 600),
        UploadedFile::fake()->image('c.jpg', 800, 600),
    ];

    Livewire::test('listing-form')
        ->set(baseFormData())
        ->set('image_files', $files)
        ->call('save')
        ->assertRedirect();

    $listing = Listing::first();
    expect($listing->images)->toHaveCount(3);
    expect($listing->images->pluck('position')->toArray())->toBe([0, 1, 2]);
    Storage::disk('public')->assertExists(
        str_replace('/storage/', '', $listing->images->first()->url)
    );
});

it('uses first image as primary when no image_url is set', function () {
    $this->actingAs($this->owner);

    Livewire::test('listing-form')
        ->set(baseFormData())
        ->set('image_files', [UploadedFile::fake()->image('main.jpg')])
        ->call('save')
        ->assertRedirect();

    $listing = Listing::first();
    expect($listing->primary_image_url)->toContain('/storage/listings/');
});

it('rejects more than 10 images at once', function () {
    $this->actingAs($this->owner);

    $files = collect()->range(1, 11)
        ->map(fn ($i) => UploadedFile::fake()->image("img{$i}.jpg"))
        ->all();

    Livewire::test('listing-form')
        ->set(baseFormData())
        ->set('image_files', $files)
        ->call('save')
        ->assertHasErrors(['image_files']);
});

it('lets owner remove an existing image', function () {
    $listing = Listing::factory()->for($this->owner)->create();
    $img = ListingImage::create([
        'listing_id' => $listing->id,
        'url' => '/storage/listings/x.jpg',
        'position' => 0,
    ]);

    $this->actingAs($this->owner);

    Livewire::test('listing-form', ['listing' => $listing])
        ->call('removeExistingImage', $img->id);

    expect(ListingImage::find($img->id))->toBeNull();
});

it('cascades image deletion when listing is deleted', function () {
    $listing = Listing::factory()->for($this->owner)->create();
    ListingImage::create([
        'listing_id' => $listing->id,
        'url' => '/storage/listings/x.jpg',
        'position' => 0,
    ]);

    expect(ListingImage::count())->toBe(1);

    $listing->delete();

    expect(ListingImage::count())->toBe(0);
});

it('appends new images to existing gallery on update', function () {
    $listing = Listing::factory()->for($this->owner)->create();
    ListingImage::create([
        'listing_id' => $listing->id,
        'url' => '/storage/listings/old.jpg',
        'position' => 0,
    ]);

    $this->actingAs($this->owner);

    Livewire::test('listing-form', ['listing' => $listing])
        ->set('image_files', [UploadedFile::fake()->image('new.jpg')])
        ->call('save')
        ->assertRedirect();

    $listing->refresh();
    expect($listing->images)->toHaveCount(2);
    expect($listing->images->pluck('position')->toArray())->toBe([0, 1]);
});
