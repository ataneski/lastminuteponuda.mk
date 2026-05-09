<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/**
 * Generates a 1080x1080 PNG promo card for a listing — sized for
 * Instagram feed posts. Cached to public storage so the same
 * file is served on subsequent requests until the listing changes.
 */
class SocialCardGenerator
{
    private const SIZE = 1080;

    private const CACHE_DIR = 'social-cards';

    private string $fontRegular;

    private string $fontBold;

    public function __construct()
    {
        $this->fontRegular = resource_path('fonts/DejaVuSans.ttf');
        $this->fontBold = resource_path('fonts/DejaVuSans-Bold.ttf');
    }

    /**
     * Returns the public URL of the cached PNG, generating it if missing
     * or stale.
     */
    public function urlFor(Listing $listing): string
    {
        $path = $this->cachedPath($listing);

        $disk = Storage::disk('public');
        $relative = self::CACHE_DIR.'/'.basename($path);

        if (! $disk->exists($relative)
            || $disk->lastModified($relative) < $listing->updated_at->getTimestamp()
        ) {
            $disk->put($relative, $this->renderPng($listing));
        }

        return Storage::url($relative);
    }

    private function cachedPath(Listing $listing): string
    {
        return self::CACHE_DIR.'/listing-'.$listing->id.'.png';
    }

    public function renderPng(Listing $listing): string
    {
        $size = self::SIZE;
        $img = imagecreatetruecolor($size, $size);
        imagesavealpha($img, true);

        $accentHex = $listing->user?->accent ?? User::DEFAULT_ACCENT;
        [$ar, $ag, $ab] = $this->hexToRgb($accentHex);

        // Background: vertical gradient from accent (top) to white (bottom).
        for ($y = 0; $y < $size; $y++) {
            $t = $y / $size;
            $r = (int) ($ar + (255 - $ar) * $t);
            $g = (int) ($ag + (255 - $ag) * $t);
            $b = (int) ($ab + (255 - $ab) * $t);
            $color = imagecolorallocate($img, $r, $g, $b);
            imageline($img, 0, $y, $size, $y, $color);
        }

        $white = imagecolorallocate($img, 255, 255, 255);
        $dark = imagecolorallocate($img, 17, 24, 39);
        $accent = imagecolorallocate($img, $ar, $ag, $ab);

        // Accent badge top-left
        imagefilledrectangle($img, 60, 60, 360, 110, $accent);
        $this->writeText($img, 'LAST MINUTE', 70, 95, 22, $white, true);

        // Title (wrap to max 3 lines)
        $title = $listing->title;
        $titleLines = $this->wrapText($title, $this->fontBold, 64, $size - 120);
        $y = 240;
        foreach (array_slice($titleLines, 0, 3) as $line) {
            $this->writeText($img, $line, 60, $y, 64, $dark, true);
            $y += 84;
        }

        // Destination + nights
        $destLine = $listing->destination.', '.$listing->country.'  ·  '.$listing->nights.' ноќи';
        $this->writeText($img, $destLine, 60, $y + 50, 36, $dark, false);

        // Big price block
        $priceText = number_format($listing->price_per_person, 0, ',', '.').' '.$listing->currency;
        $bbox = imagettfbbox(120, 0, $this->fontBold, $priceText);
        $priceW = abs($bbox[2] - $bbox[0]);
        $this->writeText($img, $priceText, $size - 60 - $priceW, $size - 220, 120, $accent, true);
        $this->writeText($img, 'по лице', $size - 60 - 110, $size - 160, 28, $dark, false);

        // Footer
        $brand = $listing->user?->brand_name ?? $listing->agency_name;
        $this->writeText($img, $brand, 60, $size - 100, 28, $dark, true);
        $this->writeText($img, 'lastminuteponuda.mk', 60, $size - 60, 24, $accent, false);

        ob_start();
        imagepng($img);
        $bytes = ob_get_clean();
        imagedestroy($img);

        return $bytes;
    }

    private function writeText($img, string $text, int $x, int $y, int $size, int $color, bool $bold): void
    {
        $font = $bold ? $this->fontBold : $this->fontRegular;
        imagettftext($img, $size, 0, $x, $y, $color, $font, $text);
    }

    /**
     * Word-wrap text using a TTF font's metric. Returns an array of lines.
     */
    private function wrapText(string $text, string $font, int $fontSize, int $maxWidth): array
    {
        $words = preg_split('/\s+/u', $text) ?: [];
        $lines = [];
        $current = '';
        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current.' '.$word;
            $bbox = imagettfbbox($fontSize, 0, $font, $candidate);
            $width = abs($bbox[2] - $bbox[0]);
            if ($width <= $maxWidth) {
                $current = $candidate;
            } else {
                if ($current !== '') {
                    $lines[] = $current;
                }
                $current = $word;
            }
        }
        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    }

    /** @return array{0:int,1:int,2:int} */
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }
}
