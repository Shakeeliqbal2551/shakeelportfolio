<?php

namespace Tests\Feature;

use App\Models\AboutSection;
use App\Models\Experience;
use App\Models\Portfolio;
use App\Models\Post;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortfolioPublicPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_portfolio_page_renders_with_seeded_content(): void
    {
        $user = User::factory()->create(['name' => 'Jane Developer']);

        $portfolio = Portfolio::create([
            'user_id' => $user->id,
            'slug' => 'jane-developer',
            'site_title' => 'Jane Developer — Portfolio',
            'hero_title' => 'I build great things',
            'hero_reassurance_items' => ['a', 'b', 'c'],
            'hero_stats' => [
                ['label' => 'L1', 'value' => 'V1'],
            ],
        ]);

        AboutSection::create([
            'portfolio_id' => $portfolio->id,
            'heading' => 'About Me',
        ]);

        Experience::create([
            'portfolio_id' => $portfolio->id,
            'company' => 'Acme Corp',
            'role' => 'Senior Engineer',
            'date_range' => '2020 — 2024',
            'description' => 'Did great work.',
            'sort_order' => 0,
        ]);

        $response = $this->get(route('portfolio.show', $portfolio->slug));

        $response->assertOk();
        $response->assertSee('Acme Corp');
        $response->assertSee('Senior Engineer');
        $response->assertSee('Jane Developer — Portfolio');
    }

    public function test_unknown_portfolio_slug_returns_404(): void
    {
        $this->get('/portfolio/does-not-exist')->assertNotFound();
    }

    public function test_root_page_renders(): void
    {
        $user = User::factory()->create();

        Portfolio::create([
            'user_id' => $user->id,
            'slug' => 'shakeel-iqbal-cheema',
            'site_title' => 'Default Portfolio',
            'hero_reassurance_items' => ['a'],
            'hero_stats' => [['label' => 'L1', 'value' => 'V1']],
        ]);

        $this->get('/')->assertOk();
    }

    public function test_blog_index_shows_published_posts_but_not_drafts(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::create([
            'user_id' => $user->id,
            'slug' => 'blog-tenant',
            'site_title' => 'Blog Tenant',
        ]);

        Post::create([
            'portfolio_id' => $portfolio->id,
            'title' => 'Published Post',
            'slug' => 'published-post',
            'body' => '<p>Published body</p>',
            'published_at' => now()->subDay(),
        ]);

        Post::create([
            'portfolio_id' => $portfolio->id,
            'title' => 'Draft Post',
            'slug' => 'draft-post',
            'body' => '<p>Draft body</p>',
            'published_at' => null,
        ]);

        $response = $this->get(route('portfolio.blog.index', $portfolio->slug));

        $response->assertOk();
        $response->assertSee('Published Post');
        $response->assertDontSee('Draft Post');
    }

    public function test_draft_post_404s_for_unauthenticated_visitor(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::create([
            'user_id' => $user->id,
            'slug' => 'blog-tenant-2',
            'site_title' => 'Blog Tenant 2',
        ]);

        $post = Post::create([
            'portfolio_id' => $portfolio->id,
            'title' => 'Draft Post',
            'slug' => 'draft-post',
            'body' => '<p>Draft body</p>',
            'published_at' => null,
        ]);

        $this->get(route('portfolio.blog.show', [$portfolio->slug, $post->slug]))->assertNotFound();
    }

    public function test_draft_post_visible_to_authenticated_owner(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::create([
            'user_id' => $user->id,
            'slug' => 'blog-tenant-3',
            'site_title' => 'Blog Tenant 3',
        ]);

        $post = Post::create([
            'portfolio_id' => $portfolio->id,
            'title' => 'Owner Draft Post',
            'slug' => 'owner-draft-post',
            'body' => '<p>Draft body for owner preview</p>',
            'published_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('portfolio.blog.show', [$portfolio->slug, $post->slug]));

        $response->assertOk();
        $response->assertSee('Owner Draft Post');
    }

    public function test_venture_role_badge_renders_for_non_client_projects_only(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::create([
            'user_id' => $user->id,
            'slug' => 'venture-tenant',
            'site_title' => 'Venture Tenant',
            'hero_reassurance_items' => ['a'],
            'hero_stats' => [['label' => 'L1', 'value' => 'V1']],
        ]);

        Project::create([
            'portfolio_id' => $portfolio->id,
            'title' => 'Founder Project',
            'description' => 'A founder-built product.',
            'role' => 'founder',
            'sort_order' => 0,
        ]);

        Project::create([
            'portfolio_id' => $portfolio->id,
            'title' => 'Client Project',
            'description' => 'Client work.',
            'role' => 'client',
            'sort_order' => 1,
        ]);

        $response = $this->get(route('portfolio.show', $portfolio->slug));

        $response->assertOk();
        $response->assertSee('Founder');
    }
}
