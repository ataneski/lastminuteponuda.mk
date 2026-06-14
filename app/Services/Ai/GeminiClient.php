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

    /**
     * Fetch an external URL and extract a list of last-minute offers
     * from its HTML. Returns ['success' => bool, 'listings' => [...], ...].
     *
     * Each listing in the array has the same shape as a Listing model
     * (subset of fields). Missing fields are null and the agency fills
     * them in during review.
     */
    public function extractListingsFromUrl(string $url): array
    {
        if (! config('services.gemini.key')) {
            return ['success' => false, 'error' => 'gemini_key_missing'];
        }

        // Fetch HTML with a realistic browser user agent. Some sites
        // block default Guzzle/Laravel UA.
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; lastminuteponudaBot/1.0; +https://lastminuteponuda.mk/bot)',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'mk,sr,bg,en;q=0.7',
                ])
                ->get($url);
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'fetch_failed: '.$e->getMessage()];
        }

        if (! $response->successful()) {
            return ['success' => false, 'error' => 'http_'.$response->status()];
        }

        $html = $response->body();
        if (strlen($html) < 200) {
            return ['success' => false, 'error' => 'empty_or_too_small'];
        }

        // Strip <script>, <style>, <svg>, <noscript> to cut tokens.
        $html = preg_replace('/<(script|style|noscript|svg)\b[^<]*(?:(?!<\/\1>)<[^<]*)*<\/\1>/is', '', $html);
        // Hard cap on size — Gemini handles up to ~1MB easily but no need
        // to send more than 200KB of cleaned HTML.
        $html = mb_substr($html, 0, 200_000);

        $body = [
            'contents' => [['parts' => [['text' => $this->extractionPrompt($url, $html)]]]],
            'generationConfig' => [
                'temperature' => 0.2,
                'response_mime_type' => 'application/json',
                'response_schema' => $this->extractionResponseSchema(),
            ],
        ];

        $endpoint = rtrim(config('services.gemini.endpoint'), '/').
            '/models/'.config('services.gemini.model').
            ':generateContent?key='.config('services.gemini.key');

        try {
            $resp = Http::timeout(60)->retry(2, 1000, throw: false)->post($endpoint, $body);
        } catch (ConnectionException $e) {
            return ['success' => false, 'error' => 'gemini_connection_failed'];
        }

        if (! $resp->successful()) {
            Log::warning('Gemini extraction non-2xx', ['status' => $resp->status()]);
            return ['success' => false, 'error' => 'gemini_http_'.$resp->status()];
        }

        $rawText = data_get($resp->json(), 'candidates.0.content.parts.0.text');
        if (! $rawText) {
            return ['success' => false, 'error' => 'gemini_empty_response'];
        }

        $parsed = json_decode($rawText, true);
        if (! is_array($parsed) || ! isset($parsed['listings']) || ! is_array($parsed['listings'])) {
            return ['success' => false, 'error' => 'malformed_json', 'raw' => $rawText];
        }

        // Normalise + resolve relative image URLs to absolute.
        $base = parse_url($url);
        $origin = isset($base['scheme'], $base['host']) ? "{$base['scheme']}://{$base['host']}" : '';
        $listings = [];
        foreach ($parsed['listings'] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $row['image_urls'] = array_values(array_filter(
                array_map(
                    fn ($u) => $this->absoluteUrl((string) $u, $url, $origin),
                    $row['image_urls'] ?? []
                ),
                fn ($u) => $u !== null
            ));
            $row['features'] = array_values(array_filter(
                $row['features'] ?? [],
                fn ($f) => is_string($f) && trim($f) !== ''
            ));
            $listings[] = $row;
        }

        return [
            'success' => true,
            'listings' => $listings,
            'usage' => data_get($resp->json(), 'usageMetadata'),
        ];
    }

    private function absoluteUrl(string $maybeRelative, string $pageUrl, string $origin): ?string
    {
        if ($maybeRelative === '') {
            return null;
        }
        if (str_starts_with($maybeRelative, 'http://') || str_starts_with($maybeRelative, 'https://')) {
            return $maybeRelative;
        }
        if (str_starts_with($maybeRelative, '//')) {
            return 'https:'.$maybeRelative;
        }
        if (str_starts_with($maybeRelative, '/')) {
            return $origin.$maybeRelative;
        }
        // Resolve against the page URL's directory
        $parts = parse_url($pageUrl);
        if (! isset($parts['scheme'], $parts['host'])) {
            return null;
        }
        $dir = isset($parts['path']) ? rtrim(dirname($parts['path']), '/') : '';

        return "{$parts['scheme']}://{$parts['host']}{$dir}/{$maybeRelative}";
    }

    private function extractionPrompt(string $url, string $html): string
    {
        return <<<PROMPT
Од HTML-от подолу, извлечи СИТЕ last-minute туристички понуди. Се работи за
македонска агенциска страница ({$url}). Врати JSON со полето "listings" (низа).

За секоја понуда извлечи (што може да најдеш):
- title (предложи краток наслов на македонски, на пр. "7 ноќи Анталија — Royal Seginus 5★")
- hotel_name (име на хотелот)
- destination (град/регион)
- country (држава)
- hotel_stars (1–5, цел број; пробај да го најдеш или procени)
- board_type — еден од: noBoard, breakfast, halfBoard, fullBoard, allInclusive, ultraAllInclusive
- transport — еден од: bus, plane, ownTransport, ferry
- departure_date (Y-m-d ако е достапен)
- return_date (Y-m-d ако е достапен)
- nights (цел број, ако е достапен)
- price_per_person (цел број, без валута знак)
- currency — еден од: EUR, MKD, USD (стандардно EUR ако цената е €/EUR; MKD ако е денари)
- available_seats (по можност, инаку 2)
- description (краток опис на македонски, 2–4 реченици)
- features (низа од стрингови, на пр. ["Базен", "All inclusive", "Wi-Fi"])
- image_urls (низа од src URL-и на сликите на понудата; апсолутни ако се можно, или релативни — ние ќе ги поправиме)

Ако едно поле го нема — користи null. Не измислувај термини/цени.
Ако HTML-от воопшто нема last-minute понуди, врати празна низа.

HTML:
{$html}
PROMPT;
    }

    private function extractionResponseSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'listings' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => ['type' => 'string', 'nullable' => true],
                            'hotel_name' => ['type' => 'string', 'nullable' => true],
                            'destination' => ['type' => 'string', 'nullable' => true],
                            'country' => ['type' => 'string', 'nullable' => true],
                            'hotel_stars' => ['type' => 'integer', 'nullable' => true],
                            'board_type' => ['type' => 'string', 'nullable' => true],
                            'transport' => ['type' => 'string', 'nullable' => true],
                            'departure_date' => ['type' => 'string', 'nullable' => true],
                            'return_date' => ['type' => 'string', 'nullable' => true],
                            'nights' => ['type' => 'integer', 'nullable' => true],
                            'price_per_person' => ['type' => 'integer', 'nullable' => true],
                            'currency' => ['type' => 'string', 'nullable' => true],
                            'available_seats' => ['type' => 'integer', 'nullable' => true],
                            'description' => ['type' => 'string', 'nullable' => true],
                            'features' => ['type' => 'array', 'items' => ['type' => 'string']],
                            'image_urls' => ['type' => 'array', 'items' => ['type' => 'string']],
                        ],
                    ],
                ],
            ],
            'required' => ['listings'],
        ];
    }
}
