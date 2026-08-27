<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function show(Portfolio $portfolio, string $project): View
    {
        $project = $portfolio->projects()->where('slug', $project)->firstOrFail();

        return view('projects.show', ['portfolio' => $portfolio, 'project' => $project]);
    }

    public function showDefault(Request $request, string $project): View
    {
        return $this->show($this->resolvedPortfolio($request), $project);
    }

    private function resolvedPortfolio(Request $request): Portfolio
    {
        return $request->attributes->get('resolvedPortfolio') ?? Portfolio::default();
    }
}
