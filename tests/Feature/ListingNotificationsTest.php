<?php

use App\Mail\ListingPublishedMail;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

function fullFormData(): array
{
    return [
        'agency_name' => 'A',
        'agency_contact' => 'a@a.mk',
        'title' => 'Notification test',
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

beforeEach(function () {
    $this->user = User::factory()->create(['email' => 'agency@example.mk']);
    RateLimiter::clear('create-listing:'.$this->user->id);
});

it('queues a confirmation email after creation', function () {
    Mail::fake();
    $this->actingAs($this->user);

    Livewire::test('listing-form')
        ->set(fullFormData())
        ->call('save');

    Mail::assertQueued(ListingPublishedMail::class, function (ListingPublishedMail $mail) {
        return $mail->hasTo('agency@example.mk');
    });
});

it('does not send email on update', function () {
    Mail::fake();
    $listing = Listing::factory()->for($this->user)->create();
    $this->actingAs($this->user);

    Livewire::test('listing-form', ['listing' => $listing])
        ->set('title', 'Промена')
        ->call('save');

    Mail::assertNothingQueued();
});

it('rate limits to 5 listings per hour for Pro users', function () {
    Mail::fake();
    $this->user->update(['subscription_tier' => 'pro', 'subscription_until' => now()->addYear()]);
    $this->actingAs($this->user);

    for ($i = 0; $i < 5; $i++) {
        Livewire::test('listing-form')
            ->set(fullFormData())
            ->set('title', "Оглас {$i}")
            ->call('save')
            ->assertRedirect();
    }

    expect(Listing::count())->toBe(5);

    Livewire::test('listing-form')
        ->set(fullFormData())
        ->set('title', 'Шести')
        ->call('save')
        ->assertHasErrors(['agency_name']);

    expect(Listing::count())->toBe(5);
});
