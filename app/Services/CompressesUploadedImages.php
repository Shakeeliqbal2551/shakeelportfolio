<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

class CompressesUploadedImages
{
    private const MAX_DIMENSION = 1600;

    private const QUALITY = 80;

    /**
     * Resize and re-encode an uploaded image as WebP before storing it, so
     * large phone/screenshot uploads don't ship to the browser at full
     * resolution and format.
     */
    public static function store(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        $manager = new ImageManager(new Driver);

        $image = $manager->decodePath($file->getRealPath());
        $image->scaleDown(width: self::MAX_DIMENSION, height: self::MAX_DIMENSION);

        $encoded = $image->encode(new WebpEncoder(quality: self::QUALITY));

        $path = $directory.'/'.Str::random(40).'.webp';

        Storage::disk($disk)->put($path, (string) $encoded);

        return $path;
    }
}
