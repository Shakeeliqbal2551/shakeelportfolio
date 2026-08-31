<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Storage;

/**
 * Resolves a stored file path (resume, image, avatar, etc.) into a public URL.
 *
 * Two path styles need to be supported side-by-side:
 *  - Legacy/seeded content still points at static files shipped in `public/img/...`
 *    (e.g. "img/portfolio/remetrichealth.png") — these are resolved with asset().
 *  - Files uploaded through the dashboard are stored on the `public` disk under
 *    "portfolios/{portfolio_id}/..." — these are resolved with Storage::url().
 */
trait HasResolvableFileUrl
{
    public static function resolveFileUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'portfolios/')) {
            return Storage::disk('public')->url($path);
        }

        // Static portfolio images were converted from PNG/JPG to WebP. Some
        // existing production records can still contain the old extension,
        // so prefer the converted file when the original no longer exists.
        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($extension, ['png', 'jpg', 'jpeg'], true)) {
            $webpPath = preg_replace('/\.(?:png|jpe?g)$/i', '.webp', $path);

            if ($webpPath && ! file_exists(public_path($path)) && file_exists(public_path($webpPath))) {
                $path = $webpPath;
            }
        }

        return asset($path);
    }
}
