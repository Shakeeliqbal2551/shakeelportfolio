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

        Portfolio::with(['posts' => fn ($query) => $query->published(), 'projects'])->each(function (Portfolio $portfolio) use (&$urls, $defaultPortfolioId) {
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

        $projects = $portfolio->relationLoaded('projects')
            ? $portfolio->projects
            : $portfolio->projects()->get();

        foreach ($projects as $project) {
            $urls[] = [
                'loc' => $isDefault ? route('work.show', $project->slug) : route('portfolio.work.show', [$portfolio, $project->slug]),
                'lastmod' => $project->updated_at,
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
            // Explicitly welcome AI/LLM crawlers so tenant sites can be cited
            // by AI answer engines, not just indexed by classic search.
            'User-agent: GPTBot',
            'Allow: /',
            '',
            'User-agent: ChatGPT-User',
            'Allow: /',
            '',
            'User-agent: ClaudeBot',
            'Allow: /',
            '',
            'User-agent: Claude-User',
            'Allow: /',
            '',
            'User-agent: anthropic-ai',
            'Allow: /',
            '',
            'User-agent: PerplexityBot',
            'Allow: /',
            '',
            'User-agent: Google-Extended',
            'Allow: /',
            '',
            'Sitemap: '.route('sitemap'),
            'Sitemap: '.route('llms-txt'),
        ];

        return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain']);
    }

    public function llmsTxt(Request $request): Response
    {
        $isDefault = ! $request->attributes->has('resolvedPortfolio');
        $portfolio = $request->attributes->get('resolvedPortfolio') ?: Portfolio::default();

        $portfolio->loadMissing(['about', 'user', 'projects', 'services', 'posts' => fn ($query) => $query->published()->latest('published_at')]);

        $name = $portfolio->user?->name ?: $portfolio->site_title ?: 'Portfolio';
        $bio = $portfolio->meta_description ?: $portfolio->about?->bio;
        $root = url('/');

        $lines = [
            '# '.$name,
            '',
        ];

        if ($bio) {
            $lines[] = '> '.$bio;
            $lines[] = '';
        }

        if ($portfolio->hero_subtitle) {
            $lines[] = $portfolio->hero_subtitle;
            $lines[] = '';
        }

        $lines[] = '## Site';
        $lines[] = '';
        $lines[] = '- ['.$name.' — Home]('.$root.'): Portfolio homepage, about, experience, and contact details.';

        if ($portfolio->contact_email) {
            $lines[] = '- Contact: '.$portfolio->contact_email;
        }

        if ($portfolio->projects->isNotEmpty()) {
            $lines[] = '';
            $lines[] = '## Projects';
            $lines[] = '';

            foreach ($portfolio->projects as $project) {
                $desc = trim((string) ($project->description ?? ''));
                $projectUrl = $isDefault
                    ? route('work.show', $project->slug)
                    : route('portfolio.work.show', [$portfolio, $project->slug]);
                $lines[] = '- ['.$project->title.']('.$projectUrl.')'.($desc !== '' ? ': '.$desc : '');
            }
        }

        if ($portfolio->services->isNotEmpty()) {
            $lines[] = '';
            $lines[] = '## Services';
            $lines[] = '';

            foreach ($portfolio->services as $service) {
                $desc = trim((string) ($service->description ?? ''));
                $lines[] = '- '.$service->title.($desc !== '' ? ': '.$desc : '');
            }
        }

        if ($portfolio->posts->isNotEmpty()) {
            $lines[] = '';
            $lines[] = '## Blog';
            $lines[] = '';

            $blogIndexUrl = $isDefault ? route('blog.index') : route('portfolio.blog.index', $portfolio);
            $lines[] = '- ['.'Blog index]('.$blogIndexUrl.')';

            foreach ($portfolio->posts as $post) {
                $postUrl = $isDefault
                    ? route('blog.show', $post->slug)
                    : route('portfolio.blog.show', [$portfolio, $post->slug]);

                $excerpt = trim((string) ($post->excerpt ?? ''));
                $lines[] = '- ['.$post->title.']('.$postUrl.')'.($excerpt !== '' ? ': '.$excerpt : '');
            }
        }

        return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
