<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ResumeController extends Controller
{
    public function __invoke(Request $request, Portfolio $portfolio): BinaryFileResponse
    {
        $resolvedPortfolio = $request->attributes->get('resolvedPortfolio');

        // A custom domain may only serve the resume belonging to that domain.
        abort_if($resolvedPortfolio && ! $resolvedPortfolio->is($portfolio), 404);

        $path = $portfolio->about?->resume_path;
        abort_unless($path, 404);

        if (str_starts_with($path, 'portfolios/')) {
            abort_unless(Storage::disk('public')->exists($path), 404);
            $absolutePath = Storage::disk('public')->path($path);
        } else {
            $absolutePath = public_path($path);
            abort_unless(is_file($absolutePath), 404);
        }

        $name = Str::slug($portfolio->user?->name ?: $portfolio->slug).'-resume.pdf';

        return response()->download($absolutePath, $name, ['Content-Type' => 'application/pdf']);
    }
}
