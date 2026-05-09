<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Standardize raw photos uploaded by an agency for use in a listing
 * gallery: EXIF-based auto-rotation, resize to a sane max width,
 * encode as JPEG with consistent quality. Returns the public path
 * (relative to the public disk) of the processed image.
 *
 * Phase 4 will add an optional brand watermark using the agency's
 * accent color; for now we leave the image untouched apart from
 * dimensional/EXIF normalization.
 */
class ImageProcessor
{
    private const MAX_WIDTH = 1600;

    private const JPEG_QUALITY = 85;

    private const OUTPUT_DISK = 'public';

    private const OUTPUT_DIR = 'listings';

    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver);
    }

    /**
     * Process raw image bytes and persist them to the public disk.
     * Returns ['path' => 'listings/abc.jpg', 'url' => '/storage/listings/abc.jpg'].
     */
    public function processBytes(string $bytes): array
    {
        $img = $this->manager->read($bytes);

        // EXIF-based rotation: phone photos sent over WhatsApp/iOS often
        // have orientation only in EXIF. Without this they appear rotated.
        $img = $img->orient();

        if ($img->width() > self::MAX_WIDTH) {
            $img = $img->scaleDown(width: self::MAX_WIDTH);
        }

        $encoded = (string) $img->toJpeg(self::JPEG_QUALITY);

        $filename = self::OUTPUT_DIR.'/'.Str::random(40).'.jpg';
        Storage::disk(self::OUTPUT_DISK)->put($filename, $encoded);

        return [
            'path' => $filename,
            'url' => Storage::disk(self::OUTPUT_DISK)->url($filename),
        ];
    }

    /**
     * Convenience: process an already-stored file at $sourcePath
     * (relative to OUTPUT_DISK) and return the processed result.
     * The original is left in place; caller decides whether to delete it.
     */
    public function processStoredFile(string $sourcePath): array
    {
        $bytes = Storage::disk(self::OUTPUT_DISK)->get($sourcePath);

        return $this->processBytes($bytes);
    }
}
