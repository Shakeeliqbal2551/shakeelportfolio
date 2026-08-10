<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Models\Post;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(Portfolio $portfolio): View
    {
        $posts = $portfolio->posts()->published()->orderBy('published_at', 'desc')->get();

        return view('blog.index', ['portfolio' => $portfolio, 'posts' => $posts]);
    }

    public function indexDefault(): View
    {
        return $this->index(Portfolio::default());
    }

    public function show(Portfolio $portfolio, string $post): View
    {
        $post = $portfolio->posts()->where('slug', $post)->firstOrFail();

        $this->authorizePostView($portfolio, $post);

        return view('blog.show', ['portfolio' => $portfolio, 'post' => $post]);
    }

    public function showDefault(string $post): View
    {
        return $this->show(Portfolio::default(), $post);
    }

    private function authorizePostView(Portfolio $portfolio, Post $post): void
    {
        if ($post->isPublished()) {
            return;
        }

        abort_unless(auth()->check() && auth()->id() === $portfolio->user_id, 404);
    }
}
