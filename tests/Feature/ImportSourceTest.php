<?php

use App\Jobs\SyncImportSourceJob;
use App\Models\ImportSource;
use App\Models\Listing;
use App\Models\User;
use App\Services\Ai\GeminiClient;
use App\Services\ImageProcessor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    config()->set('services.gemini.key', 'test-key');
    config()->set('services.gemini.model', 'gemini-2.5-pro');
    config()->set('services.gemini.endpoint', 'https://generativelanguage.googleapis.com/v1beta');

    $this->agency = User::factory()->create(['role' => 'agency']);
});

it('redirects guests away from import sources index', function () {
    $this->get('/agencija/auto-import')->assertRedirect('/login');
});

it('shows empty state for an agency with no sources', function () {
    $this->actingAs($this->agency)
        ->get('/agencija/auto-import')
        ->assertOk()
        ->assertSee('Сè уште нема извор');
});

it('creates a new source and redirects to its detail page', function () {
    $this->actingAs($this->agency)
        ->post('/agencija/auto-import', [
            'url' => 'https://aries.mk/st_hotel/last-minute-corner/',
            'label' => 'Aries Hotel',
            'schedule' => '6h',
        ])
        ->assertRedirect();

    $source = ImportSource::firstOrFail();
    expect($source->user_id)->toBe($this->agency->id);
    expect($source->url)->toBe('https://aries.mk/st_hotel/last-minute-corner/');
    expect($source->schedule)->toBe('6h');
});

it('shows 404 for someone elses source', function () {
    $other = User::factory()->create(['role' => 'agency']);
    $source = ImportSource::create([
        'user_id' => $other->id,
        'url' => 'https://example.com/x',
        'schedule' => 'manual',
        'active' => true,
    ]);

    $this->actingAs($this->agency)
        ->get(route('agency.import-sources.show', $source))
        ->assertNotFound();
});

it('sync job creates draft listings from extracted offers', function () {
    $source = ImportSource::create([
        'user_id' => $this->agency->id,
        'url' => 'https://aries.mk/st_hotel/last-minute-corner/',
        'schedule' => 'manual',
        'active' => true,
    ]);

    Http::fake([
        'aries.mk/*' => Http::response('<html><body>'.str_repeat('last-minute offer html ', 20).'</body></html>', 200),
        '*generativelanguage.googleapis.com*' => Http::response([
            'candidates' => [[
                'content' => ['parts' => [[
                    'text' => json_encode([
                        'listings' => [
                            [
                                'title' => '7 ноќи Анталија — Royal Seginus 5★',
                                'hotel_name' => 'Royal Seginus',
                                'destination' => 'Анталија',
                                'country' => 'Турција',
                                'hotel_stars' => 5,
                                'board_type' => 'allInclusive',
                                'transport' => 'plane',
                                'departure_date' => '2026-07-15',
                                'return_date' => '2026-07-22',
                                'nights' => 7,
                                'price_per_person' => 599,
                                'currency' => 'EUR',
                                'available_seats' => 4,
                                'description' => 'Прекрасен 5-ѕвезден хотел на плажа',
                                'features' => ['Базен', 'Wi-Fi'],
                                'image_urls' => [],
                            ],
                        ],
                    ]),
                ]]],
            ]],
        ], 200),
    ]);

    (new SyncImportSourceJob($source->id))->handle(app(GeminiClient::class), app(ImageProcessor::class));

    $source->refresh();
    expect($source->last_status)->toBe('success');
    expect($source->last_extracted_count)->toBe(1);

    $listing = Listing::firstOrFail();
    expect($listing->user_id)->toBe($this->agency->id);
    expect($listing->title)->toBe('7 ноќи Анталија — Royal Seginus 5★');
    expect($listing->isDraft())->toBeTrue();
    expect($listing->price_per_person)->toBe(599);
});

it('deduplicates listings within 30 days', function () {
    $source = ImportSource::create([
        'user_id' => $this->agency->id,
        'url' => 'https://aries.mk/x',
        'schedule' => 'manual',
        'active' => true,
    ]);
    // Pre-existing identical listing
    Listing::factory()->for($this->agency)->create([
        'title' => 'Duplicate offer',
        'departure_date' => '2026-08-01',
        'price_per_person' => 500,
    ]);

    Http::fake([
        'aries.mk/*' => Http::response('<html>'.str_repeat('content body ', 30).'</html>', 200),
        '*generativelanguage.googleapis.com*' => Http::response([
            'candidates' => [[
                'content' => ['parts' => [[
                    'text' => json_encode([
                        'listings' => [[
                            'title' => 'Duplicate offer',
                            'departure_date' => '2026-08-01',
                            'price_per_person' => 500,
                            'image_urls' => [],
                            'features' => [],
                        ]],
                    ]),
                ]]],
            ]],
        ], 200),
    ]);

    (new SyncImportSourceJob($source->id))->handle(app(GeminiClient::class), app(ImageProcessor::class));

    expect(Listing::count())->toBe(1);
    expect($source->fresh()->last_extracted_count)->toBe(0);
});

it('records failure when Gemini returns 5xx', function () {
    $source = ImportSource::create([
        'user_id' => $this->agency->id,
        'url' => 'https://aries.mk/x',
        'schedule' => 'manual',
        'active' => true,
    ]);

    Http::fake([
        'aries.mk/*' => Http::response('<html>some content with at least 200 chars '.str_repeat('a', 250).'</html>', 200),
        '*generativelanguage.googleapis.com*' => Http::response('boom', 500),
    ]);

    (new SyncImportSourceJob($source->id))->handle(app(GeminiClient::class), app(ImageProcessor::class));

    $source->refresh();
    expect($source->last_status)->toBe('failed');
    expect($source->last_error)->toContain('gemini_http_500');
    expect($source->consecutive_failures)->toBe(1);
});

it('auto-deactivates after 3 consecutive failures', function () {
    $source = ImportSource::create([
        'user_id' => $this->agency->id,
        'url' => 'https://aries.mk/x',
        'schedule' => 'manual',
        'active' => true,
        'consecutive_failures' => 2,
    ]);

    Http::fake([
        'aries.mk/*' => Http::response('<html>some content with at least 200 chars '.str_repeat('a', 250).'</html>', 200),
        '*generativelanguage.googleapis.com*' => Http::response('boom', 500),
    ]);

    (new SyncImportSourceJob($source->id))->handle(app(GeminiClient::class), app(ImageProcessor::class));

    expect($source->fresh()->active)->toBeFalse();
});

it('isDue returns true only for active, non-manual sources past their cadence', function () {
    $source = ImportSource::create([
        'user_id' => $this->agency->id,
        'url' => 'https://x.mk',
        'schedule' => 'hourly',
        'active' => true,
        'last_synced_at' => now()->subHours(2),
    ]);
    expect($source->isDue())->toBeTrue();

    $source->update(['last_synced_at' => now()->subMinutes(30)]);
    expect($source->fresh()->isDue())->toBeFalse();

    $source->update(['schedule' => 'manual']);
    expect($source->fresh()->isDue())->toBeFalse();

    $source->update(['schedule' => 'hourly', 'active' => false]);
    expect($source->fresh()->isDue())->toBeFalse();
});

it('toggle pauses and resumes an active source', function () {
    $source = ImportSource::create([
        'user_id' => $this->agency->id,
        'url' => 'https://x.mk',
        'schedule' => 'daily',
        'active' => true,
    ]);

    $this->actingAs($this->agency)
        ->post(route('agency.import-sources.toggle', $source))
        ->assertRedirect();

    expect($source->fresh()->active)->toBeFalse();

    $this->actingAs($this->agency)
        ->post(route('agency.import-sources.toggle', $source))
        ->assertRedirect();

    expect($source->fresh()->active)->toBeTrue();
});

it('destroy deletes the source but keeps existing listings', function () {
    $source = ImportSource::create([
        'user_id' => $this->agency->id,
        'url' => 'https://x.mk',
        'schedule' => 'manual',
        'active' => true,
    ]);
    Listing::factory()->for($this->agency)->create();

    $this->actingAs($this->agency)
        ->delete(route('agency.import-sources.destroy', $source))
        ->assertRedirect(route('agency.import-sources'));

    expect(ImportSource::find($source->id))->toBeNull();
    expect(Listing::count())->toBe(1);
});

it('extractListingsFromUrl resolves relative image URLs to absolute', function () {
    Http::fake([
        'aries.mk/*' => Http::response('<html>'.str_repeat('content body ', 30).'</html>', 200),
        '*generativelanguage.googleapis.com*' => Http::response([
            'candidates' => [[
                'content' => ['parts' => [[
                    'text' => json_encode([
                        'listings' => [[
                            'title' => 'X',
                            'image_urls' => ['/img/hotel.jpg', '//cdn.example.com/y.jpg', 'https://abs.example/z.jpg'],
                            'features' => [],
                        ]],
                    ]),
                ]]],
            ]],
        ], 200),
    ]);

    $client = app(GeminiClient::class);
    $result = $client->extractListingsFromUrl('https://aries.mk/st_hotel/last-minute-corner/');

    expect($result['success'])->toBeTrue();
    $urls = $result['listings'][0]['image_urls'];
    expect($urls)->toContain('https://aries.mk/img/hotel.jpg');
    expect($urls)->toContain('https://cdn.example.com/y.jpg');
    expect($urls)->toContain('https://abs.example/z.jpg');
});

it('returns failure when fetch HTTP returns 4xx', function () {
    Http::fake(['aries.mk/*' => Http::response('not found', 404)]);

    $result = app(GeminiClient::class)->extractListingsFromUrl('https://aries.mk/missing');

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toBe('http_404');
});

it('sync command dispatches due sources only', function () {
    \Illuminate\Support\Facades\Queue::fake();

    ImportSource::create([
        'user_id' => $this->agency->id,
        'url' => 'https://due.mk',
        'schedule' => 'hourly',
        'active' => true,
        'last_synced_at' => now()->subHours(2),
    ]);
    ImportSource::create([
        'user_id' => $this->agency->id,
        'url' => 'https://notdue.mk',
        'schedule' => 'hourly',
        'active' => true,
        'last_synced_at' => now()->subMinutes(10),
    ]);
    ImportSource::create([
        'user_id' => $this->agency->id,
        'url' => 'https://manual.mk',
        'schedule' => 'manual',
        'active' => true,
    ]);

    $this->artisan('import:sync-due')->assertSuccessful();

    \Illuminate\Support\Facades\Queue::assertPushed(SyncImportSourceJob::class, 1);
});
