<?php

namespace App\Jobs;

use App\Models\ImportSource;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Services\Ai\GeminiClient;
use App\Services\ImageProcessor;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fetch an ImportSource URL via Gemini, create draft Listings from the
 * extracted offers, and download/process their images. Idempotent —
 * dedup is based on (user_id, normalized title + departure_date + price)
 * within the last 30 days.
 *
 * Always updates source.last_synced_at + last_status — even on failure —
 * so the admin/agency UI can show fresh diagnostics.
 */
class SyncImportSourceJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public $tries = 2;

    public $backoff = [60, 300];

    public function __construct(public int $sourceId) {}

    public function uniqueId(): string
    {
        return (string) $this->sourceId;
    }

    public function handle(GeminiClient $gemini, ImageProcessor $processor): void
    {
        $source = ImportSource::with('user')->find($this->sourceId);
        if (! $source || ! $source->active) {
            return;
        }

        $result = $gemini->extractListingsFromUrl($source->url);

        if (! $result['success']) {
            $source->recordFailure($result['error'] ?? 'unknown');

            return;
        }

        $extracted = $result['listings'] ?? [];
        $createdCount = 0;

        foreach ($extracted as $row) {
            if ($this->isDuplicate($source, $row)) {
                continue;
            }

            $listing = $this->createDraftListing($source, $row);
            $this->attachImages($listing, $row['image_urls'] ?? [], $processor);
            $createdCount++;
        }

        $source->recordSuccess($createdCount);
    }

    /**
     * Skip if this agency already has a listing matching title +
     * departure_date + price_per_person from the last 30 days.
     */
    private function isDuplicate(ImportSource $source, array $row): bool
    {
        $title = trim((string) ($row['title'] ?? ''));
        if ($title === '') {
            return false;
        }
        $normalised = mb_strtolower(preg_replace('/\s+/u', ' ', $title));

        return Listing::query()
            ->where('user_id', $source->user_id)
            ->whereRaw('LOWER(TRIM(title)) = ?', [$normalised])
            ->when($row['departure_date'] ?? null, fn ($q, $d) => $q->whereDate('departure_date', $d))
            ->when($row['price_per_person'] ?? null, fn ($q, $p) => $q->where('price_per_person', $p))
            ->where('created_at', '>=', now()->subDays(30))
            ->exists();
    }

    private function createDraftListing(ImportSource $source, array $row): Listing
    {
        $user = $source->user;
        $departure = $this->parseDate($row['departure_date'] ?? null) ?? now()->addWeek()->toDateString();
        $return = $this->parseDate($row['return_date'] ?? null) ?? now()->addWeeks(2)->toDateString();

        $stars = (int) ($row['hotel_stars'] ?? 4);
        $stars = max(1, min(5, $stars));

        return Listing::create([
            'user_id' => $user->id,
            'agency_name' => $user->display_name ?: $user->name,
            'agency_contact' => $user->phone ?: $user->email,
            'title' => $this->safeString($row['title'] ?? null) ?? 'Понуда без наслов',
            'destination' => $this->safeString($row['destination'] ?? null) ?? '—',
            'country' => $this->safeString($row['country'] ?? null) ?? '—',
            'hotel_name' => $this->safeString($row['hotel_name'] ?? null) ?? '—',
            'hotel_stars' => $stars,
            'board_type' => $this->validBoard($row['board_type'] ?? null),
            'transport' => $this->validTransport($row['transport'] ?? null),
            'departure_date' => $departure,
            'return_date' => $return,
            'nights' => max(1, (int) ($row['nights'] ?? 7)),
            'price_per_person' => max(1, (int) ($row['price_per_person'] ?? 1)),
            'currency' => in_array($row['currency'] ?? null, ['EUR', 'MKD', 'USD'], true) ? $row['currency'] : 'EUR',
            'available_seats' => max(1, (int) ($row['available_seats'] ?? 2)),
            'description' => $this->safeString($row['description'] ?? null) ?? '',
            'features' => array_slice($row['features'] ?? [], 0, 20),
            'published_at' => null, // draft — agency reviews and clicks publish
        ]);
    }

    private function attachImages(Listing $listing, array $urls, ImageProcessor $processor): void
    {
        $cap = (int) ($listing->user->feature('max_images_per_listing') ?? 10);
        $i = 0;
        foreach (array_unique($urls) as $url) {
            if ($i >= $cap) {
                break;
            }
            try {
                $resp = Http::timeout(15)
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0 (lastminuteponudaBot/1.0)'])
                    ->get($url);
                if (! $resp->successful()) {
                    continue;
                }
                $bytes = $resp->body();
                if (strlen($bytes) < 1000 || strlen($bytes) > 8_000_000) {
                    continue;
                }
                $stored = $processor->processBytes($bytes);
                ListingImage::create([
                    'listing_id' => $listing->id,
                    'url' => $stored['url'],
                    'position' => $i++,
                ]);
            } catch (\Throwable $e) {
                Log::info('image download failed', ['url' => $url, 'error' => $e->getMessage()]);
            }
        }
        if ($first = $listing->images()->orderBy('position')->first()) {
            $listing->update(['image_url' => $first->url]);
        }
    }

    private function parseDate(?string $s): ?string
    {
        if (! $s) {
            return null;
        }
        try {
            return \Carbon\Carbon::parse($s)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function safeString(mixed $s): ?string
    {
        if (! is_string($s)) {
            return null;
        }
        $s = trim($s);

        return $s === '' ? null : mb_substr($s, 0, 5000);
    }

    private function validBoard(?string $v): string
    {
        $allowed = array_keys(\App\Models\Listing::BOARD_TYPES);

        return in_array($v, $allowed, true) ? $v : 'allInclusive';
    }

    private function validTransport(?string $v): string
    {
        $allowed = array_keys(\App\Models\Listing::TRANSPORTS);

        return in_array($v, $allowed, true) ? $v : 'plane';
    }
}
