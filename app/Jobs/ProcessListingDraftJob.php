<?php

namespace App\Jobs;

use App\Models\Listing;
use App\Services\Ai\GeminiClient;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

/**
 * Async pipeline that polishes a listing draft after upload:
 *   1. Reads the bytes of every attached ListingImage from storage.
 *   2. Calls Gemini for a suggested title + best image ordering.
 *   3. Persists the title (only if the agency hasn't typed one)
 *      and reorders ListingImage.position.
 *   4. Stamps ai_generated_at + ai_raw_response on the listing so
 *      the wizard's review step (wire:poll) knows we're done.
 *
 * Failures degrade gracefully: the listing keeps the agency-supplied
 * title (or a fallback derived from the form) and the original upload
 * order. ai_generated_at is still set so the wizard stops polling.
 */
class ProcessListingDraftJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /** @var int */
    public $tries = 3;

    /** @var array<int, int> */
    public $backoff = [30, 120, 300];

    public function __construct(public int $listingId) {}

    public function uniqueId(): string
    {
        return (string) $this->listingId;
    }

    public function handle(GeminiClient $gemini): void
    {
        $listing = Listing::with('images')->find($this->listingId);
        if (! $listing) {
            return;
        }

        // Already processed — idempotent if retried after a partial failure.
        if ($listing->ai_generated_at !== null) {
            return;
        }

        $images = $listing->images;
        if ($images->isEmpty()) {
            $listing->forceFill([
                'ai_generated_at' => now(),
                'ai_raw_response' => ['error' => 'no_images_to_process'],
            ])->save();

            return;
        }

        // Fetch bytes for each image. Storage::url() returned /storage/...
        // when written; convert to disk-relative path.
        $imageBytes = [];
        foreach ($images as $image) {
            $relative = ltrim(str_replace('/storage/', '', $image->url), '/');
            try {
                $bytes = Storage::disk('public')->get($relative);
            } catch (\Throwable $e) {
                $bytes = null;
            }
            if ($bytes !== null) {
                $imageBytes[] = $bytes;
            }
        }

        $result = $gemini->generateListingDraft($listing, $imageBytes);

        if ($result['success']) {
            // Reorder gallery according to AI suggestion (indices map to
            // the images collection in original DB order).
            foreach ($result['image_order'] as $newPos => $sourceIdx) {
                if (isset($images[$sourceIdx])) {
                    $images[$sourceIdx]->update(['position' => $newPos]);
                }
            }

            // Update primary image_url to the new first image so card
            // previews reflect the AI ordering.
            $newPrimary = $listing->images()->orderBy('position')->first();
            if ($newPrimary) {
                $listing->image_url = $newPrimary->url;
            }

            // Only overwrite the title if the agency hasn't typed a real
            // one. Empty string OR a placeholder generated from form data
            // means "use AI's suggestion".
            if (trim($listing->title) === '' || $listing->title === $this->fallbackTitle($listing)) {
                $listing->title = $result['title'];
            }
        }

        $listing->forceFill([
            'ai_generated_at' => now(),
            'ai_raw_response' => [
                'success' => $result['success'],
                'error' => $result['error'] ?? null,
                'usage' => $result['usage'] ?? null,
                'suggested_title' => $result['title'] ?? null,
                'image_order' => $result['image_order'] ?? null,
            ],
        ])->save();
    }

    private function fallbackTitle(Listing $listing): string
    {
        return "{$listing->nights} ноќи {$listing->destination}";
    }
}
