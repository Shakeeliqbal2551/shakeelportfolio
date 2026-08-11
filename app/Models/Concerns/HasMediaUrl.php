<?php

namespace App\Models\Concerns;

use App\Services\MediaService;

/**
 * Provides url($attribute) helper that resolves a stored media path
 * through the centralised MediaService.
 */
trait HasMediaUrl
{
    public function mediaUrl(?string $path): ?string
    {
        return app(MediaService::class)->url($path);
    }
}
