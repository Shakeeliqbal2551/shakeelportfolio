<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(Portfolio $portfolio): View
    {
        $posts = $portfolio->posts()->published()->orderBy('published_at', 'desc')->get();

        return view('blog.index', ['portfolio' => $portfolio, 'posts' => $posts]);
    }

    public function indexDefault(Request $request): View
    {
        return $this->index($this->resolvedPortfolio($request));
    }

    public function show(Portfolio $portfolio, string $post): View
    {
        $post = $portfolio->posts()->where('slug', $post)->firstOrFail();

        $this->authorizePostView($portfolio, $post);

        return view('blog.show', ['portfolio' => $portfolio, 'post' => $post]);
    }

    public function showDefault(Request $request, string $post): View
    {
        return $this->show($this->resolvedPortfolio($request), $post);
    }

    private function resolvedPortfolio(Request $request): Portfolio
    {
        return $request->attributes->get('resolvedPortfolio') ?? Portfolio::default();
    }

    private function authorizePostView(Portfolio $portfolio, Post $post): void
    {
        if ($post->isPublished()) {
            return;
        }

        abort_unless(auth()->check() && auth()->id() === $portfolio->user_id, 404);
    }
}
