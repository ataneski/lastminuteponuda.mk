<?php

namespace App\Jobs;

use App\Models\Listing;
use App\Models\WhatsAppIntakeSession;
use App\Services\WhatsAppClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

/**
 * Wraps a WhatsApp intake session into a draft Listing and sends the
 * agency a link to finish the CPT form on web.
 *
 * The actual AI title/order processing happens later via
 * ProcessListingDraftJob, which we dispatch from here.
 */
class FinalizeWhatsAppIntakeJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;

    public $backoff = [10, 60, 180];

    public function __construct(public int $sessionId) {}

    public function handle(WhatsAppClient $whatsapp): void
    {
        $session = WhatsAppIntakeSession::with('user')->find($this->sessionId);
        if (! $session) {
            return;
        }
        if ($session->state === WhatsAppIntakeSession::STATE_DONE) {
            return;
        }
        if (empty($session->media_paths)) {
            $session->update(['state' => WhatsAppIntakeSession::STATE_DONE]);

            return;
        }

        $user = $session->user;

        // Create a barebones draft. CPT fields get filled by the agency
        // on the web wizard step 3 (linked to listings.create-ai with
        // ?from=whatsapp&session=ID — implementation in Phase 4).
        $listing = Listing::create([
            'user_id' => $user->id,
            'agency_name' => $user->display_name ?: $user->name,
            'agency_contact' => $user->phone ?: $user->email,
            'title' => '',
            'destination' => '',
            'country' => '',
            'hotel_name' => '',
            'hotel_stars' => 4,
            'board_type' => 'allInclusive',
            'transport' => 'plane',
            'departure_date' => now()->addWeek(),
            'return_date' => now()->addWeeks(2),
            'nights' => 7,
            'price_per_person' => 0,
            'currency' => 'EUR',
            'available_seats' => 1,
            'description' => '',
            'features' => [],
            'published_at' => null,
        ]);

        // Attach already-processed images from the session.
        foreach ($session->media_paths as $i => $path) {
            $listing->images()->create([
                'url' => Storage::disk('public')->url($path),
                'position' => $i,
            ]);
        }
        $first = $listing->images()->orderBy('position')->first();
        if ($first) {
            $listing->update(['image_url' => $first->url]);
        }

        $session->update([
            'state' => WhatsAppIntakeSession::STATE_DONE,
            'listing_id' => $listing->id,
        ]);

        // Dispatch AI processing.
        ProcessListingDraftJob::dispatch($listing->id);

        // Notify the agency on WhatsApp with a link to finish CPT.
        $url = route('listings.create-ai', ['session' => $session->id]);
        $whatsapp->sendText(
            $session->wa_phone_e164,
            "Примивме ".count($session->media_paths)." слики. ".
            "Допиши цена + датуми тука: {$url}"
        );
    }
}
