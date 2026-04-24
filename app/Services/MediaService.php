<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Centralised media handling. Every upload, delete, and URL lookup in the
 * application goes through this service so storage providers can be swapped
 * (local -> S3 -> Spaces) by changing MEDIA_DISK in .env.
 */
class MediaService
{
    /**
     * Store an uploaded file in the given logical bucket and return the
     * relative path on the configured disk.
     */
    public function store(UploadedFile $file, string $bucket = 'misc', ?string $filename = null): string
    {
        $folder = $this->folder($bucket);
        $name   = $filename ?: $this->generateName($file);

        $this->disk()->putFileAs($folder, $file, $name);

        return trim($folder.'/'.$name, '/');
    }

    /**
     * Store a raw string (e.g. encoded image) at a chosen path.
     */
    public function put(string $path, string $contents): string
    {
        $this->disk()->put($path, $contents);

        return $path;
    }

    /**
     * Delete a file from the configured disk if it exists.
     */
    public function delete(?string $path): bool
    {
        if (! $path) {
            return false;
        }

        return $this->disk()->delete($path);
    }

    /**
     * Public URL for a stored path. Returns null when path is empty.
     */
    public function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        // Already a full URL? pass through (allows external avatars etc.).
        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return $this->disk()->url($path);
    }

    /**
     * Absolute filesystem path (only meaningful for local disks).
     */
    public function path(string $path): string
    {
        return $this->disk()->path($path);
    }

    /**
     * Has the file? Useful for safety checks.
     */
    public function exists(?string $path): bool
    {
        return $path && $this->disk()->exists($path);
    }

    /**
     * Resolve the storage disk used for media.
     */
    public function disk(): Filesystem
    {
        return Storage::disk(config('media.disk', 'public'));
    }

    /**
     * Resolve the folder for a logical bucket. Falls back to 'misc'.
     */
    public function folder(string $bucket): string
    {
        return config("media.paths.$bucket", config('media.paths.misc', 'portfolio/misc'));
    }

    /**
     * Generate a collision-resistant filename while keeping the original extension.
     */
    protected function generateName(UploadedFile $file): string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');

        return now()->format('YmdHis').'-'.Str::lower(Str::random(10)).'.'.$ext;
    }
}
