<?php

use App\Mail\ListingInquiryMail;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
    RateLimiter::clear('inquiry:127.0.0.1');

    $this->agency = User::factory()->create(['email' => 'agency@example.mk']);
    $this->listing = Listing::factory()->for($this->agency)->create();
});

it('shows the inquiry form on the listing detail to anonymous visitors', function () {
    $this->get(route('listings.show', $this->listing))
        ->assertOk()
        ->assertSee('Прашајте ја агенцијата');
});

it('hides the inquiry form from the listings owner', function () {
    $this->actingAs($this->agency)
        ->get(route('listings.show', $this->listing))
        ->assertOk()
        ->assertDontSee('Прашајте ја агенцијата');
});

it('queues an inquiry email to the agency', function () {
    Livewire::test('listing-inquiry', ['listing' => $this->listing])
        ->set('name', 'Иван Петров')
        ->set('email', 'ivan@example.com')
        ->set('phone', '+389 70 111 222')
        ->set('bodyMessage', 'Барам термин за двајца возрасни во август.')
        ->call('submit')
        ->assertSet('sent', true);

    Mail::assertQueued(ListingInquiryMail::class, function (ListingInquiryMail $mail) {
        return $mail->hasTo('agency@example.mk')
            && $mail->senderName === 'Иван Петров'
            && $mail->senderEmail === 'ivan@example.com';
    });
});

it('rejects empty fields', function () {
    Livewire::test('listing-inquiry', ['listing' => $this->listing])
        ->call('submit')
        ->assertHasErrors(['name', 'email', 'bodyMessage']);

    Mail::assertNothingQueued();
});

it('rejects invalid email', function () {
    Livewire::test('listing-inquiry', ['listing' => $this->listing])
        ->set('name', 'X')
        ->set('email', 'not-an-email')
        ->set('bodyMessage', 'Доволно долга порака за тестирање.')
        ->call('submit')
        ->assertHasErrors(['email']);
});

it('silently swallows submissions when honeypot is filled', function () {
    Livewire::test('listing-inquiry', ['listing' => $this->listing])
        ->set('name', 'Spam Bot')
        ->set('email', 'bot@spam.com')
        ->set('bodyMessage', 'BUY MY THING NOW!')
        ->set('website', 'http://spam.example')
        ->call('submit')
        ->assertSet('sent', true);

    Mail::assertNothingQueued();
});

it('rate limits to 3 inquiries per hour per IP', function () {
    for ($i = 0; $i < 3; $i++) {
        Livewire::test('listing-inquiry', ['listing' => $this->listing])
            ->set('name', "Sender {$i}")
            ->set('email', "s{$i}@example.com")
            ->set('bodyMessage', "Доволно долга порака за тестирање број {$i}.")
            ->call('submit')
            ->assertSet('sent', true);
    }

    Mail::assertQueuedCount(3);

    Livewire::test('listing-inquiry', ['listing' => $this->listing])
        ->set('name', 'Fourth')
        ->set('email', 'fourth@example.com')
        ->set('bodyMessage', 'Доволно долга порака за тестирање.')
        ->call('submit')
        ->assertHasErrors(['bodyMessage']);

    Mail::assertQueuedCount(3);
});

it('falls back to agency_contact when listing has no user', function () {
    $listing = Listing::factory()->create([
        'agency_contact' => 'fallback@example.mk',
        'user_id' => null,
    ]);

    Livewire::test('listing-inquiry', ['listing' => $listing])
        ->set('name', 'X')
        ->set('email', 'sender@example.com')
        ->set('bodyMessage', 'Доволно долга порака за тестирање.')
        ->call('submit')
        ->assertSet('sent', true);

    Mail::assertQueued(ListingInquiryMail::class, fn ($m) => $m->hasTo('fallback@example.mk'));
});
