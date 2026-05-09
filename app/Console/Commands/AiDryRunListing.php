<?php

namespace App\Console\Commands;

use App\Models\Listing;
use App\Services\Ai\GeminiClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Local-only helper for prompt iteration: runs Gemini on an existing
 * listing's images and prints the suggestion. Does NOT mutate the row.
 */
#[Signature('ai:dry-run-listing {id : Listing ID}')]
#[Description('Print the AI title + image_order suggestion for an existing listing without saving.')]
class AiDryRunListing extends Command
{
    public function handle(GeminiClient $gemini): int
    {
        if (app()->isProduction()) {
            $this->error('Refusing to dry-run in production.');

            return self::FAILURE;
        }

        $id = (int) $this->argument('id');
        $listing = Listing::with('images')->find($id);
        if (! $listing) {
            $this->error("Listing {$id} not found.");

            return self::FAILURE;
        }
        if ($listing->images->isEmpty()) {
            $this->warn('Listing has no images.');

            return self::SUCCESS;
        }

        $bytes = [];
        foreach ($listing->images as $img) {
            $relative = ltrim(str_replace('/storage/', '', $img->url), '/');
            $bytes[] = Storage::disk('public')->get($relative) ?? '';
        }

        $this->info("Calling Gemini with {$listing->images->count()} images…");
        $result = $gemini->generateListingDraft($listing, array_filter($bytes));

        $this->newLine();
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
