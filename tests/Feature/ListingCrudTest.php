<?php

use App\Models\Listing;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->stranger = User::factory()->create();
    $this->listing = Listing::factory()->for($this->owner)->create();
});

it('redirects guests away from edit page', function () {
    $this->get(route('listings.edit', $this->listing))
        ->assertRedirect('/login');
});

it('forbids non-owners from viewing edit page', function () {
    $this->actingAs($this->stranger)
        ->get(route('listings.edit', $this->listing))
        ->assertForbidden();
});

it('allows the owner to view edit page', function () {
    $this->actingAs($this->owner)
        ->get(route('listings.edit', $this->listing))
        ->assertOk()
        ->assertSee('Уреди оглас');
});

it('lets the owner update their listing', function () {
    $this->actingAs($this->owner);

    Livewire::test('listing-form', ['listing' => $this->listing])
        ->set('title', 'Ажуриран наслов')
        ->set('price_per_person', 777)
        ->call('save')
        ->assertRedirect();

    $this->listing->refresh();
    expect($this->listing->title)->toBe('Ажуриран наслов');
    expect($this->listing->price_per_person)->toBe(777);
});

it('forbids non-owners from updating via Livewire', function () {
    $this->actingAs($this->stranger);

    Livewire::test('listing-form', ['listing' => $this->listing])
        ->assertForbidden();
});

it('lets the owner delete their listing', function () {
    $this->actingAs($this->owner)
        ->delete(route('listings.destroy', $this->listing))
        ->assertRedirect(route('listings.mine'));

    expect(Listing::find($this->listing->id))->toBeNull();
});

it('forbids non-owners from deleting', function () {
    $this->actingAs($this->stranger)
        ->delete(route('listings.destroy', $this->listing))
        ->assertForbidden();

    expect(Listing::find($this->listing->id))->not->toBeNull();
});

it('redirects guests trying to delete', function () {
    $this->delete(route('listings.destroy', $this->listing))
        ->assertRedirect('/login');
});
