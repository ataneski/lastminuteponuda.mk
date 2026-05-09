<?php

namespace App\Services\Ai;

use App\Models\Listing;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper over the Gemini 2.5 Pro generateContent endpoint.
 *
 * Two responsibilities for v1:
 *   1. Generate a snappy Macedonian title for a listing draft from
 *      its CPT form data + the uploaded photos.
 *   2. Pick the best ordering of the photos (hero-first) so the
 *      gallery presents the most attractive image first.
 *
 * Returns a structured array — never throws on AI errors. A failure
 * returns ['success' => false, 'error' => ...] so the calling job
 * can fall back gracefully (keep agency-supplied title, keep upload
 * order).
 */
class GeminiClient
{
    private const TIMEOUT_SECONDS = 60;

    public function generateListingDraft(Listing $listing, array $imageBytes): array
    {
        if (! config('services.gemini.key')) {
            return ['success' => false, 'error' => 'gemini_key_missing'];
        }

        if (empty($imageBytes)) {
            return ['success' => false, 'error' => 'no_images'];
        }

        $parts = [['text' => $this->prompt($listing, count($imageBytes))]];
        foreach ($imageBytes as $i => $bytes) {
            $parts[] = ['text' => "Слика #{$i}:"];
            $parts[] = [
                'inline_data' => [
                    'mime_type' => 'image/jpeg',
                    'data' => base64_encode($bytes),
                ],
            ];
        }

        $body = [
            'contents' => [['parts' => $parts]],
            'generationConfig' => [
                'temperature' => 0.4,
                'response_mime_type' => 'application/json',
                'response_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'title' => ['type' => 'string'],
                        'image_order' => [
                            'type' => 'array',
                            'items' => ['type' => 'integer'],
                        ],
                    ],
                    'required' => ['title', 'image_order'],
                ],
            ],
        ];

        $endpoint = rtrim(config('services.gemini.endpoint'), '/').
            '/models/'.config('services.gemini.model').
            ':generateContent?key='.config('services.gemini.key');

        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->retry(2, 1000, throw: false)
                ->post($endpoint, $body);
        } catch (ConnectionException $e) {
            Log::warning('Gemini connection failed', ['error' => $e->getMessage()]);

            return ['success' => false, 'error' => 'connection_failed'];
        }

        if (! $response->successful()) {
            Log::warning('Gemini non-2xx', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return ['success' => false, 'error' => 'http_'.$response->status()];
        }

        $payload = $response->json();
        $rawText = data_get($payload, 'candidates.0.content.parts.0.text');
        if (! $rawText) {
            return ['success' => false, 'error' => 'empty_response', 'raw' => $payload];
        }

        $parsed = json_decode($rawText, true);
        if (! is_array($parsed) || ! isset($parsed['title'], $parsed['image_order'])) {
            return ['success' => false, 'error' => 'malformed_json', 'raw' => $rawText];
        }

        // Defensive: validate image_order is a permutation of [0..n-1].
        $expected = range(0, count($imageBytes) - 1);
        $order = array_values(array_filter(
            $parsed['image_order'],
            fn ($v) => is_int($v) && in_array($v, $expected, true)
        ));
        $order = array_values(array_unique($order));
        if (count($order) !== count($imageBytes)) {
            // Fill in any missing indices in original order.
            foreach ($expected as $i) {
                if (! in_array($i, $order, true)) {
                    $order[] = $i;
                }
            }
        }

        return [
            'success' => true,
            'title' => trim((string) $parsed['title']),
            'image_order' => $order,
            'raw' => $payload,
            'usage' => data_get($payload, 'usageMetadata'),
        ];
    }

    private function prompt(Listing $listing, int $imageCount): string
    {
        $boardLabel = Listing::BOARD_TYPES[$listing->board_type] ?? $listing->board_type;
        $transportLabel = Listing::TRANSPORTS[$listing->transport] ?? $listing->transport;

        return <<<PROMPT
Ти си асистент за last-minute туристичка платформа. Имаш {$imageCount} слики
од хотел/смештај/локација и податоци за понудата:

- Хотел: {$listing->hotel_name} ({$listing->hotel_stars}★)
- Дестинација: {$listing->destination}, {$listing->country}
- Термин: {$listing->departure_date->format('d.m.Y')} — {$listing->return_date->format('d.m.Y')} ({$listing->nights} ноќи)
- Цена: {$listing->price_per_person} {$listing->currency} по лице
- Пансион: {$boardLabel}
- Превоз: {$transportLabel}
- Слободни места: {$listing->available_seats}

Врати ЈСОН со две полиња:

1. "title" — концизен наслов за огласот на македонски кирилица, во стилот:
   "<ноќи> ноќи <дестинација> — <хотел> <ѕвезди>★ <пансион>". Максимум
   90 знаци. Без емоџии.

2. "image_order" — низа од {$imageCount} ИНДЕКСИ (0..{$imageCount}-1, секој
   се појавува точно еднаш) кои го одредуваат најдобриот редослед на
   сликите за галерија. Хотел-екстериер или импресивен поглед оди прво
   (hero), внатрешност на соба втора, ресторан/базен/плажа потоа,
   детали на крај. Избегни блурликани/темни слики на врвот.
PROMPT;
    }
}
