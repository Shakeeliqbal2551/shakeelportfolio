<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function sitemap(Request $request): Response
    {
        // On a verified tenant custom domain, only that tenant's own URLs
        // belong in its sitemap — listing every tenant here would both be
        // wrong (other tenants don't live at this host) and a duplicate-
        // content SEO problem across domains.
        if ($resolved = $request->attributes->get('resolvedPortfolio')) {
            $urls = $this->urlsFor($resolved, isDefault: true);

            return response(view('sitemap', ['urls' => $urls])->render(), 200, ['Content-Type' => 'application/xml']);
        }

        $defaultPortfolioId = Portfolio::default()->id;

        $urls = [];

        Portfolio::with(['posts' => fn ($query) => $query->published()])->each(function (Portfolio $portfolio) use (&$urls, $defaultPortfolioId) {
            $urls = [...$urls, ...$this->urlsFor($portfolio, isDefault: $portfolio->id === $defaultPortfolioId)];
        });

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * @return array<int, array{loc: string, lastmod: mixed, priority: string}>
     */
    private function urlsFor(Portfolio $portfolio, bool $isDefault): array
    {
        $urls = [
            [
                'loc' => $isDefault ? url('/') : route('portfolio.show', $portfolio),
                'lastmod' => $portfolio->updated_at,
                'priority' => '1.0',
            ],
        ];

        $publishedPosts = $portfolio->relationLoaded('posts')
            ? $portfolio->posts
            : $portfolio->posts()->published()->get();

        if ($publishedPosts->isNotEmpty()) {
            $urls[] = [
                'loc' => $isDefault ? route('blog.index') : route('portfolio.blog.index', $portfolio),
                'lastmod' => $publishedPosts->max('updated_at'),
                'priority' => '0.8',
            ];
        }

        foreach ($publishedPosts as $post) {
            $urls[] = [
                'loc' => $isDefault ? route('blog.show', $post->slug) : route('portfolio.blog.show', [$portfolio, $post->slug]),
                'lastmod' => $post->updated_at,
                'priority' => '0.7',
            ];
        }

        return $urls;
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /dashboard',
            'Disallow: /settings',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain']);
    }
}
