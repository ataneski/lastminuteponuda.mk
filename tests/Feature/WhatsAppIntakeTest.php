<?php

use App\Jobs\FinalizeWhatsAppIntakeJob;
use App\Jobs\ProcessListingDraftJob;
use App\Models\Listing;
use App\Models\User;
use App\Models\WhatsAppIntakeSession;
use App\Services\WhatsAppClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
    config()->set('services.whatsapp.access_token', 'test-token');
    config()->set('services.whatsapp.phone_number_id', '12345');
    config()->set('services.whatsapp.verify_token', 'verify-secret');
    config()->set('services.whatsapp.app_secret', null); // skip sig in test env
    config()->set('services.whatsapp.graph_endpoint', 'https://graph.facebook.com/v21.0');
});

it('GET /webhooks/whatsapp returns the challenge when verify token matches', function () {
    $this->get('/webhooks/whatsapp?hub_mode=subscribe&hub_verify_token=verify-secret&hub_challenge=42')
        ->assertOk()
        ->assertSeeText('42');
});

it('GET /webhooks/whatsapp returns 403 on bad verify token', function () {
    $this->get('/webhooks/whatsapp?hub_mode=subscribe&hub_verify_token=wrong&hub_challenge=42')
        ->assertForbidden();
});

it('rejects spoofed signatures with HTTP 200 (silent drop)', function () {
    // With a configured app_secret, the controller verifies HMAC even
    // in testing env. A bogus header should be silently dropped.
    config()->set('services.whatsapp.app_secret', 'secret-key');

    $this->postJson('/webhooks/whatsapp',
        ['entry' => [['changes' => [['value' => ['messages' => [['from' => '38970999888', 'text' => ['body' => 'X']]]]]]]]],
        ['X-Hub-Signature-256' => 'sha256=lies'],
    )->assertOk();

    expect(WhatsAppIntakeSession::count())->toBe(0);
});

it('a paired user sending an image creates a session and stores bytes', function () {
    Queue::fake();
    $user = User::factory()->create([
        'wa_phone_e164' => '+38970111222',
        'wa_paired_at' => now(),
    ]);

    Http::fake([
        '*graph.facebook.com*media-id-123*' => Http::response(['url' => 'https://example.com/file.jpg'], 200),
        'https://example.com/file.jpg' => Http::response(buildJpegBytes(), 200),
    ]);

    $this->postJson('/webhooks/whatsapp', metaPayloadWithImage(from: '38970111222', mediaId: 'media-id-123'))
        ->assertOk();

    $session = WhatsAppIntakeSession::where('user_id', $user->id)->first();
    expect($session)->not->toBeNull();
    expect($session->state)->toBe('collecting');
    expect(count($session->media_paths))->toBe(1);
    Storage::disk('public')->assertExists($session->media_paths[0]);
});

it('a stop-word from a paired user dispatches FinalizeJob', function () {
    Queue::fake();
    $user = User::factory()->create([
        'wa_phone_e164' => '+38970111222',
        'wa_paired_at' => now(),
    ]);
    $session = WhatsAppIntakeSession::create([
        'user_id' => $user->id,
        'wa_phone_e164' => '+38970111222',
        'state' => 'collecting',
        'media_paths' => ['listings/x.jpg'],
        'last_message_at' => now(),
    ]);

    $this->postJson('/webhooks/whatsapp', metaPayloadWithText(from: '38970111222', text: 'ГОТОВО'))
        ->assertOk();

    Queue::assertPushed(FinalizeWhatsAppIntakeJob::class);
    expect($session->fresh()->state)->toBe('finalizing');
});

it('an unknown sender with a valid pairing code becomes paired', function () {
    Queue::fake();
    Http::fake(['*graph.facebook.com*' => Http::response([], 200)]);

    $user = User::factory()->create([
        'wa_phone_e164' => null,
        'wa_paired_at' => null,
        'wa_pairing_code' => 'ABC123',
    ]);

    $this->postJson('/webhooks/whatsapp', metaPayloadWithText(from: '38970999888', text: 'ABC123'))
        ->assertOk();

    $user->refresh();
    expect($user->wa_phone_e164)->toBe('+38970999888');
    expect($user->wa_paired_at)->not->toBeNull();
    expect($user->wa_pairing_code)->toBeNull();
});

it('finalize job creates a draft listing and dispatches ProcessListingDraftJob', function () {
    Queue::fake();
    Http::fake(['*graph.facebook.com*' => Http::response([], 200)]);

    $user = User::factory()->create([
        'wa_phone_e164' => '+38970111222',
        'wa_paired_at' => now(),
    ]);
    Storage::disk('public')->put('listings/a.jpg', 'a');
    Storage::disk('public')->put('listings/b.jpg', 'b');

    $session = WhatsAppIntakeSession::create([
        'user_id' => $user->id,
        'wa_phone_e164' => '+38970111222',
        'state' => 'finalizing',
        'media_paths' => ['listings/a.jpg', 'listings/b.jpg'],
        'last_message_at' => now(),
    ]);

    (new FinalizeWhatsAppIntakeJob($session->id))->handle(app(WhatsAppClient::class));

    $session->refresh();
    expect($session->state)->toBe('done');
    expect($session->listing_id)->not->toBeNull();

    $listing = Listing::find($session->listing_id);
    expect($listing->isDraft())->toBeTrue();
    expect($listing->images)->toHaveCount(2);

    Queue::assertPushed(ProcessListingDraftJob::class);
});

it('regenerates a WhatsApp pairing code on profile', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('agency-profile-form')
        ->call('regenerateWhatsAppCode');

    $user->refresh();
    expect($user->wa_pairing_code)->not->toBeNull();
    expect(strlen($user->wa_pairing_code))->toBe(6);
});

it('unpairs a WhatsApp number on profile', function () {
    $user = User::factory()->create([
        'wa_phone_e164' => '+38970111222',
        'wa_paired_at' => now(),
    ]);
    $this->actingAs($user);

    Livewire::test('agency-profile-form')
        ->call('unpairWhatsApp');

    $user->refresh();
    expect($user->wa_phone_e164)->toBeNull();
    expect($user->wa_paired_at)->toBeNull();
    expect($user->isWhatsAppPaired())->toBeFalse();
});

// Helpers
function buildJpegBytes(): string
{
    $img = imagecreatetruecolor(400, 300);
    imagefill($img, 0, 0, imagecolorallocate($img, 100, 150, 200));
    ob_start();
    imagejpeg($img, null, 80);
    $bytes = ob_get_clean();
    imagedestroy($img);

    return $bytes;
}

function metaPayloadWithImage(string $from, string $mediaId): array
{
    return [
        'entry' => [[
            'changes' => [[
                'value' => [
                    'messages' => [[
                        'from' => $from,
                        'image' => ['id' => $mediaId, 'mime_type' => 'image/jpeg'],
                    ]],
                ],
            ]],
        ]],
    ];
}

function metaPayloadWithText(string $from, string $text): array
{
    return [
        'entry' => [[
            'changes' => [[
                'value' => [
                    'messages' => [[
                        'from' => $from,
                        'text' => ['body' => $text],
                    ]],
                ],
            ]],
        ]],
    ];
}
