<?php

use App\Models\Listing;
use Livewire\Livewire;

beforeEach(function () {
    $this->cheap = Listing::factory()->create([
        'title' => 'Цена ниска',
        'destination' => 'Анталија',
        'country' => 'Турција',
        'board_type' => 'allInclusive',
        'transport' => 'plane',
        'hotel_stars' => 3,
        'price_per_person' => 200,
        'departure_date' => now()->addDays(10)->toDateString(),
        'return_date' => now()->addDays(17)->toDateString(),
    ]);

    $this->expensive = Listing::factory()->create([
        'title' => 'Цена висока',
        'destination' => 'Касандра',
        'country' => 'Грција',
        'board_type' => 'halfBoard',
        'transport' => 'bus',
        'hotel_stars' => 5,
        'price_per_person' => 1200,
        'departure_date' => now()->addDays(30)->toDateString(),
        'return_date' => now()->addDays(35)->toDateString(),
    ]);
});

it('filters by country', function () {
    Livewire::test('listings-index')
        ->set('country', 'Турција')
        ->assertSee('Цена ниска')
        ->assertDontSee('Цена висока');
});

it('filters by board type', function () {
    Livewire::test('listings-index')
        ->set('board_type', 'halfBoard')
        ->assertSee('Цена висока')
        ->assertDontSee('Цена ниска');
});

it('filters by transport', function () {
    Livewire::test('listings-index')
        ->set('transport', 'plane')
        ->assertSee('Цена ниска')
        ->assertDontSee('Цена висока');
});

it('filters by minimum stars', function () {
    Livewire::test('listings-index')
        ->set('min_stars', '5')
        ->assertSee('Цена висока')
        ->assertDontSee('Цена ниска');
});

it('filters by price range', function () {
    Livewire::test('listings-index')
        ->set('price_min', '500')
        ->set('price_max', '1500')
        ->assertSee('Цена висока')
        ->assertDontSee('Цена ниска');
});

it('filters by departure date range', function () {
    Livewire::test('listings-index')
        ->set('departure_from', now()->addDays(20)->toDateString())
        ->assertSee('Цена висока')
        ->assertDontSee('Цена ниска');
});

it('combines multiple filters', function () {
    Livewire::test('listings-index')
        ->set('country', 'Турција')
        ->set('min_stars', '3')
        ->set('price_max', '500')
        ->assertSee('Цена ниска')
        ->assertDontSee('Цена висока');
});

it('clears all filters', function () {
    Livewire::test('listings-index')
        ->set('country', 'Турција')
        ->set('min_stars', '5')
        ->call('clearFilters')
        ->assertSet('country', '')
        ->assertSet('min_stars', '')
        ->assertSee('Цена ниска')
        ->assertSee('Цена висока');
});

it('counts active filters', function () {
    Livewire::test('listings-index')
        ->set('country', 'Турција')
        ->set('min_stars', '3')
        ->set('price_min', '100')
        ->assertSee('3'); // count badge in UI
});
