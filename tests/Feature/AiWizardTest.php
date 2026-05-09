<?php

use App\Jobs\ProcessListingDraftJob;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use App\Services\Ai\GeminiClient;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
    config()->set('services.gemini.key', 'test-key');
    config()->set('services.gemini.model', 'gemini-2.5-pro');
    config()->set('services.gemini.endpoint', 'https://generativelanguage.googleapis.com/v1beta');

    $this->owner = User::factory()->create([
        'subscription_tier' => 'pro',
        'subscription_until' => now()->addYear(),
    ]);
});

function wizardCptData(): array
{
    return [
        'title' => '',
        'destination' => 'Анталија',
        'country' => 'Турција',
        'hotel_name' => 'Royal Seginus',
        'hotel_stars' => 5,
        'board_type' => 'allInclusive',
        'transport' => 'plane',
        'departure_date' => now()->addWeek()->toDateString(),
        'return_date' => now()->addWeeks(2)->toDateString(),
        'nights' => 7,
        'price_per_person' => 599,
        'currency' => 'EUR',
        'available_seats' => 4,
        'description' => '',
    ];
}

it('redirects guests away from AI wizard route', function () {
    $this->get(route('listings.create-ai'))->assertRedirect('/login');
});

it('renders the wizard for authenticated agencies', function () {
    $this->actingAs($this->owner)
        ->get(route('listings.create-ai'))
        ->assertOk()
        ->assertSee('Нов оглас со AI');
});

it('blocks step 1 advance when fewer than 3 photos uploaded', function () {
    $this->actingAs($this->owner);

    Livewire::test('ai-listing-wizard')
        ->set('image_files', [UploadedFile::fake()->image('a.jpg')])
        ->call('nextFromUpload')
        ->assertHasErrors(['image_files']);
});

it('creates a draft listing and dispatches the AI job on step 2 submit', function () {
    Queue::fake();
    $this->actingAs($this->owner);

    $files = [
        UploadedFile::fake()->image('1.jpg', 1200, 800),
        UploadedFile::fake()->image('2.jpg', 1200, 800),
        UploadedFile::fake()->image('3.jpg', 1200, 800),
    ];

    Livewire::test('ai-listing-wizard')
        ->set('image_files', $files)
        ->call('nextFromUpload')
        ->set(wizardCptData())
        ->set('title', 'Manual title') // agency typed something
        ->call('createDraft')
        ->assertSet('step', 3);

    expect(Listing::count())->toBe(1);
    $listing = Listing::first();
    expect($listing->isDraft())->toBeTrue();
    expect($listing->user_id)->toBe($this->owner->id);
    expect($listing->images)->toHaveCount(3);

    Queue::assertPushed(ProcessListingDraftJob::class, fn ($job) => $job->listingId === $listing->id);
});

it('process job stamps ai_generated_at and reorders images on success', function () {
    $listing = Listing::factory()->draft()->for($this->owner)->create([
        'title' => '',
    ]);
    foreach (range(0, 2) as $i) {
        $path = "listings/test{$i}.jpg";
        Storage::disk('public')->put($path, "fake-bytes-{$i}");
        ListingImage::create([
            'listing_id' => $listing->id,
            'url' => Storage::disk('public')->url($path),
            'position' => $i,
        ]);
    }

    Http::fake([
        '*generativelanguage.googleapis.com*' => Http::response([
            'candidates' => [[
                'content' => ['parts' => [[
                    'text' => json_encode([
                        'title' => '7 ноќи Анталија — Royal Seginus 5★',
                        'image_order' => [2, 0, 1],
                    ], JSON_UNESCAPED_UNICODE),
                ]]],
            ]],
            'usageMetadata' => ['totalTokenCount' => 1234],
        ], 200),
    ]);

    (new ProcessListingDraftJob($listing->id))->handle(app(GeminiClient::class));

    $listing->refresh();
    expect($listing->ai_generated_at)->not->toBeNull();
    expect($listing->title)->toBe('7 ноќи Анталија — Royal Seginus 5★');
    expect($listing->ai_raw_response['success'])->toBeTrue();
    expect($listing->ai_raw_response['usage'])->toMatchArray(['totalTokenCount' => 1234]);

    $orderedIds = $listing->images()->orderBy('position')->pluck('position')->all();
    expect($orderedIds)->toBe([0, 1, 2]); // sequence of new positions

    // Index 2 of original collection should now be position 0
    $hero = $listing->images()->orderBy('position')->first();
    expect($hero->url)->toContain('test2.jpg');
});

it('does not overwrite agency-typed title', function () {
    $listing = Listing::factory()->draft()->for($this->owner)->create([
        'title' => 'Custom human title',
    ]);
    foreach (range(0, 1) as $i) {
        $path = "listings/x{$i}.jpg";
        Storage::disk('public')->put($path, 'b');
        ListingImage::create([
            'listing_id' => $listing->id,
            'url' => Storage::disk('public')->url($path),
            'position' => $i,
        ]);
    }

    Http::fake([
        '*generativelanguage.googleapis.com*' => Http::response([
            'candidates' => [[
                'content' => ['parts' => [[
                    'text' => json_encode([
                        'title' => 'AI suggested',
                        'image_order' => [0, 1],
                    ]),
                ]]],
            ]],
        ], 200),
    ]);

    (new ProcessListingDraftJob($listing->id))->handle(app(GeminiClient::class));

    expect($listing->fresh()->title)->toBe('Custom human title');
});

it('degrades gracefully on Gemini failure', function () {
    $listing = Listing::factory()->draft()->for($this->owner)->create([
        'title' => '',
    ]);
    foreach (range(0, 1) as $i) {
        $path = "listings/y{$i}.jpg";
        Storage::disk('public')->put($path, 'b');
        ListingImage::create([
            'listing_id' => $listing->id,
            'url' => Storage::disk('public')->url($path),
            'position' => $i,
        ]);
    }

    Http::fake([
        '*generativelanguage.googleapis.com*' => Http::response('Internal error', 500),
    ]);

    (new ProcessListingDraftJob($listing->id))->handle(app(GeminiClient::class));

    $fresh = $listing->fresh();
    expect($fresh->ai_generated_at)->not->toBeNull();
    expect($fresh->ai_raw_response['success'])->toBeFalse();
    expect($fresh->ai_raw_response['error'])->toStartWith('http_');
    // Original order preserved
    $positions = $fresh->images()->orderBy('id')->pluck('position')->all();
    expect($positions)->toBe([0, 1]);
});

it('Gemini client returns success false when key missing', function () {
    config()->set('services.gemini.key', null);

    $client = app(GeminiClient::class);
    $listing = Listing::factory()->create();

    $result = $client->generateListingDraft($listing, ['fakebytes']);

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toBe('gemini_key_missing');
});

it('Gemini client validates and repairs malformed image_order', function () {
    Http::fake([
        '*generativelanguage.googleapis.com*' => Http::response([
            'candidates' => [[
                'content' => ['parts' => [[
                    'text' => json_encode([
                        'title' => 'Repaired',
                        'image_order' => [99, 0, 99], // invalid + duplicate
                    ]),
                ]]],
            ]],
        ], 200),
    ]);

    $listing = Listing::factory()->create();
    $client = app(GeminiClient::class);
    $result = $client->generateListingDraft($listing, ['a', 'b', 'c']);

    expect($result['success'])->toBeTrue();
    expect($result['image_order'])->toHaveCount(3);
    expect(array_unique($result['image_order']))->toHaveCount(3);
    expect(min($result['image_order']))->toBe(0);
    expect(max($result['image_order']))->toBe(2);
});

it('publish() persists wizard edits and flips the listing live', function () {
    Queue::fake();
    $this->actingAs($this->owner);

    $files = [
        UploadedFile::fake()->image('1.jpg'),
        UploadedFile::fake()->image('2.jpg'),
        UploadedFile::fake()->image('3.jpg'),
    ];

    $component = Livewire::test('ai-listing-wizard')
        ->set('image_files', $files)
        ->call('nextFromUpload')
        ->set(wizardCptData())
        ->set('title', 'Initial title')
        ->call('createDraft');

    $draft = Listing::first();
    expect($draft->isDraft())->toBeTrue();

    $component
        ->set('title', 'Edited after AI')
        ->call('publish')
        ->assertRedirect();

    $draft->refresh();
    expect($draft->isDraft())->toBeFalse();
    expect($draft->title)->toBe('Edited after AI');
    expect($draft->published_at)->not->toBeNull();
});

it('image processor resizes images > MAX_WIDTH and outputs JPEG', function () {
    // Build a 3000x2000 JPEG in memory (no temp file → Storage::fake-safe).
    $src = imagecreatetruecolor(3000, 2000);
    imagefill($src, 0, 0, imagecolorallocate($src, 80, 120, 200));
    ob_start();
    imagejpeg($src, null, 90);
    $bytes = ob_get_clean();
    imagedestroy($src);

    $processor = app(\App\Services\ImageProcessor::class);
    $result = $processor->processBytes($bytes);

    expect($result['url'])->toContain('/storage/listings/');
    Storage::disk('public')->assertExists($result['path']);

    $processed = Storage::disk('public')->get($result['path']);
    $img = imagecreatefromstring($processed);
    expect(imagesx($img))->toBeLessThanOrEqual(1600);
});
