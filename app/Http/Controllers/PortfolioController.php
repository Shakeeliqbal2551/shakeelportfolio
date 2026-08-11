<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Services\MediaService;
use App\Support\PortfolioContext;

class PortfolioController extends Controller
{
    public function index()
    {
        $portfolio = PortfolioContext::current() ?? Portfolio::with('settings')->first();

        $settings    = $portfolio?->settings;
        $photos      = $portfolio?->profilePhotos()->where('is_active', true)->orderBy('sort_order')->get() ?? collect();
        $projectCats = $portfolio?->projectCategories()->where('is_active', true)->orderBy('sort_order')->get() ?? collect();
        $projects    = $portfolio?->projects()->where('is_published', true)
                            ->with(['categories', 'primaryImage', 'images'])
                            ->orderBy('sort_order')->get() ?? collect();
        $skillCats   = $portfolio?->skillCategories()->where('is_active', true)
                            ->with(['skills' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
                            ->orderBy('sort_order')->get() ?? collect();
        $skills      = $portfolio?->skills()->where('is_active', true)->orderBy('sort_order')->get() ?? collect();
        $experiences = $portfolio?->experiences()->where('is_visible', true)->orderBy('sort_order')->get() ?? collect();
        $educations  = $portfolio?->educations()->where('is_visible', true)->orderBy('sort_order')->get() ?? collect();
        $services    = $portfolio?->services()->where('is_active', true)->orderBy('sort_order')->get() ?? collect();
        $testimonials= $portfolio?->testimonials()->where('is_visible', true)->orderBy('sort_order')->get() ?? collect();
        $whyPoints   = $portfolio?->whyPoints()->where('is_visible', true)->orderBy('sort_order')->get() ?? collect();

        $heroPhotoUrl = $photos->isNotEmpty()
            ? app(MediaService::class)->url($photos->random()->path)
            : asset('img/shakeel1.png');

        $photoUrls = $photos->isNotEmpty()
            ? $photos->map(fn ($p) => app(MediaService::class)->url($p->path))->values()->all()
            : [asset('img/shakeel1.png')];

        return view('site.portfolio', [
            'portfolio'         => $portfolio,
            'settings'          => $settings,
            'photos'            => $photos,
            'photoUrls'         => $photoUrls,
            'heroPhotoUrl'      => $heroPhotoUrl,
            'projectCategories' => $projectCats,
            'projects'          => $projects,
            'skillCategories'   => $skillCats,
            'skills'            => $skills,
            'experiences'       => $experiences,
            'educations'        => $educations,
            'services'          => $services,
            'testimonials'      => $testimonials,
            'whyPoints'         => $whyPoints,
        ]);
    }
}
