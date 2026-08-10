<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function sitemap(): Response
    {
        $defaultPortfolioId = Portfolio::default()->id;

        $urls = [];

        Portfolio::with(['posts' => fn ($query) => $query->published()])->each(function (Portfolio $portfolio) use (&$urls, $defaultPortfolioId) {
            $isDefault = $portfolio->id === $defaultPortfolioId;

            $urls[] = [
                'loc' => $isDefault ? url('/') : route('portfolio.show', $portfolio),
                'lastmod' => $portfolio->updated_at,
                'priority' => '1.0',
            ];

            $publishedPosts = $portfolio->posts;

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
        });

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml']);
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
