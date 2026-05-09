<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper around Meta's WhatsApp Cloud Graph API.
 *
 * Two responsibilities for v1:
 *   1. Resolve a media-id (received in webhook payload) to its bytes.
 *      Meta's media URL expires 5 minutes after we GET /{media-id},
 *      so the caller must keep this synchronous.
 *   2. Send a text reply to a user we received a message from
 *      (e.g. "your draft is ready, finish it here: <link>").
 */
class WhatsAppClient
{
    public function downloadMediaBytes(string $mediaId): ?string
    {
        $token = config('services.whatsapp.access_token');
        $endpoint = rtrim(config('services.whatsapp.graph_endpoint'), '/');
        if (! $token) {
            return null;
        }

        // Step 1: get the (short-lived) download URL.
        $meta = Http::withToken($token)->get("{$endpoint}/{$mediaId}");
        if (! $meta->successful()) {
            return null;
        }
        $url = $meta->json('url');
        if (! $url) {
            return null;
        }

        // Step 2: download bytes immediately. Meta requires the token
        // header on this request as well.
        $bytes = Http::withToken($token)->get($url);
        if (! $bytes->successful()) {
            return null;
        }

        return $bytes->body();
    }

    public function sendText(string $toE164, string $message): Response
    {
        $token = config('services.whatsapp.access_token');
        $phoneId = config('services.whatsapp.phone_number_id');
        $endpoint = rtrim(config('services.whatsapp.graph_endpoint'), '/');

        return Http::withToken($token)
            ->post("{$endpoint}/{$phoneId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => ltrim($toE164, '+'),
                'type' => 'text',
                'text' => ['body' => $message],
            ]);
    }
}
