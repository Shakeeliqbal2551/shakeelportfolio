<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TenantMediaController extends Controller
{
    public function __invoke(Request $request, string $path): BinaryFileResponse
    {
        abort_unless(preg_match('#^portfolios/(\d+)/#', $path, $matches) === 1, 404);

        $portfolioId = (int) $matches[1];
        $resolvedPortfolio = $request->attributes->get('resolvedPortfolio');

        // On a custom domain, never expose another tenant's uploaded media.
        abort_if($resolvedPortfolio instanceof Portfolio && $resolvedPortfolio->id !== $portfolioId, 404);
        abort_unless(Storage::disk('public')->exists($path), 404);

        return response()->file(Storage::disk('public')->path($path), [
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
