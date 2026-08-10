<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapAndRobotsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_returns_valid_xml_with_published_post_but_not_draft(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::create([
            'user_id' => $user->id,
            'slug' => 'shakeel-iqbal-cheema',
            'site_title' => 'Default Portfolio',
        ]);

        Post::create([
            'portfolio_id' => $portfolio->id,
            'title' => 'Published Post',
            'slug' => 'published-post',
            'body' => '<p>Body</p>',
            'published_at' => now()->subDay(),
        ]);

        Post::create([
            'portfolio_id' => $portfolio->id,
            'title' => 'Draft Post',
            'slug' => 'draft-post',
            'body' => '<p>Body</p>',
            'published_at' => null,
        ]);

        $response = $this->get(route('sitemap'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml');

        $content = $response->getContent();
        $this->assertStringContainsString('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', $content);
        $this->assertStringContainsString(url('/'), $content);
        $this->assertStringContainsString(route('blog.show', 'published-post'), $content);
        $this->assertStringNotContainsString(route('blog.show', 'draft-post'), $content);
    }

    public function test_sitemap_lists_default_portfolio_root_url_only_once(): void
    {
        $user = User::factory()->create();

        Portfolio::create([
            'user_id' => $user->id,
            'slug' => 'shakeel-iqbal-cheema',
            'site_title' => 'Default Portfolio',
        ]);

        $content = $this->get(route('sitemap'))->getContent();

        $this->assertStringNotContainsString(route('portfolio.show', 'shakeel-iqbal-cheema'), $content);
        $this->assertSame(1, substr_count($content, '<loc>'.url('/').'</loc>'));
    }

    public function test_sitemap_uses_portfolio_show_route_for_non_default_portfolios(): void
    {
        $defaultUser = User::factory()->create();
        Portfolio::create([
            'user_id' => $defaultUser->id,
            'slug' => 'shakeel-iqbal-cheema',
            'site_title' => 'Default Portfolio',
        ]);

        $otherUser = User::factory()->create();
        $otherPortfolio = Portfolio::create([
            'user_id' => $otherUser->id,
            'slug' => 'other-tenant',
            'site_title' => 'Other Tenant',
        ]);

        $content = $this->get(route('sitemap'))->getContent();

        $this->assertStringContainsString(route('portfolio.show', $otherPortfolio), $content);
    }

    public function test_robots_txt_returns_200_and_references_sitemap(): void
    {
        $response = $this->get(route('robots'));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('Sitemap:', $content);
        $this->assertStringContainsString(route('sitemap'), $content);
        $this->assertStringContainsString('Disallow: /dashboard', $content);
    }

    public function test_canonical_host_middleware_does_not_redirect_in_testing_environment(): void
    {
        $this->assertFalse(app()->isProduction());

        $user = User::factory()->create();
        Portfolio::create([
            'user_id' => $user->id,
            'slug' => 'shakeel-iqbal-cheema',
            'site_title' => 'Default Portfolio',
        ]);

        $this->get('/sitemap.xml')->assertOk();
    }
}
