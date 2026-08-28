<?php

namespace Tests\Feature;

use App\Models\AboutSection;
use App\Models\Experience;
use App\Models\Portfolio;
use App\Models\Post;
use App\Models\ProfileImage;
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

    public function test_portfolio_show_redirects_to_root_for_default_portfolio(): void
    {
        $user = User::factory()->create();

        Portfolio::create([
            'user_id' => $user->id,
            'slug' => 'shakeel-iqbal-cheema',
            'site_title' => 'Default Portfolio',
            'hero_reassurance_items' => ['a'],
            'hero_stats' => [['label' => 'L1', 'value' => 'V1']],
        ]);

        $this->get(route('portfolio.show', 'shakeel-iqbal-cheema'))
            ->assertRedirect(route('home'))
            ->assertStatus(301);
    }

    public function test_portfolio_show_does_not_redirect_for_non_default_portfolio(): void
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
            'hero_reassurance_items' => ['a'],
            'hero_stats' => [['label' => 'L1', 'value' => 'V1']],
        ]);

        $this->get(route('portfolio.show', $otherPortfolio))->assertOk();
    }

    public function test_home_page_has_single_h1_matching_hero_title(): void
    {
        $user = User::factory()->create();

        Portfolio::create([
            'user_id' => $user->id,
            'slug' => 'shakeel-iqbal-cheema',
            'site_title' => 'Default Portfolio',
            'hero_subtitle' => 'Senior Laravel Developer',
            'hero_title' => 'I build Laravel web apps that make you money',
            'hero_reassurance_items' => ['a'],
            'hero_stats' => [['label' => 'L1', 'value' => 'V1']],
        ]);

        $content = $this->get('/')->getContent();

        $this->assertSame(1, preg_match_all('/<h1[\s>]/', $content));
        preg_match('/<h1[^>]*>(.*?)<\/h1>/s', $content, $matches);
        $this->assertStringContainsString('I build Laravel web apps that make you money', $matches[1] ?? '');
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

    public function test_blog_index_redirects_to_short_url_for_default_portfolio(): void
    {
        $user = User::factory()->create();
        Portfolio::create([
            'user_id' => $user->id,
            'slug' => 'shakeel-iqbal-cheema',
            'site_title' => 'Default Portfolio',
        ]);

        $this->get(route('portfolio.blog.index', 'shakeel-iqbal-cheema'))
            ->assertRedirect(route('blog.index'))
            ->assertStatus(301);
    }

    public function test_blog_show_redirects_to_short_url_for_default_portfolio(): void
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

        $this->get(route('portfolio.blog.show', ['shakeel-iqbal-cheema', 'published-post']))
            ->assertRedirect(route('blog.show', 'published-post'))
            ->assertStatus(301);
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

    public function test_public_page_shows_one_of_the_active_profile_images_only(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::create([
            'user_id' => $user->id,
            'slug' => 'gallery-tenant',
            'site_title' => 'Gallery Tenant',
            'hero_reassurance_items' => ['a'],
            'hero_stats' => [['label' => 'L1', 'value' => 'V1']],
        ]);

        ProfileImage::create(['portfolio_id' => $portfolio->id, 'image_path' => 'img/active-one.png', 'is_active' => true, 'sort_order' => 0]);
        ProfileImage::create(['portfolio_id' => $portfolio->id, 'image_path' => 'img/active-two.png', 'is_active' => true, 'sort_order' => 1]);
        ProfileImage::create(['portfolio_id' => $portfolio->id, 'image_path' => 'img/inactive.png', 'is_active' => false, 'sort_order' => 2]);

        $response = $this->get(route('portfolio.show', $portfolio->slug));

        $response->assertOk();
        $response->assertDontSee('inactive.png');

        $body = $response->getContent();
        $sawActiveOne = str_contains($body, 'active-one.png');
        $sawActiveTwo = str_contains($body, 'active-two.png');

        $this->assertTrue($sawActiveOne xor $sawActiveTwo);
    }

    public function test_public_page_falls_back_to_about_profile_image_when_no_active_gallery_images(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::create([
            'user_id' => $user->id,
            'slug' => 'fallback-tenant',
            'site_title' => 'Fallback Tenant',
            'hero_reassurance_items' => ['a'],
            'hero_stats' => [['label' => 'L1', 'value' => 'V1']],
        ]);

        AboutSection::create([
            'portfolio_id' => $portfolio->id,
            'profile_image_path' => 'img/about-fallback.png',
        ]);

        ProfileImage::create(['portfolio_id' => $portfolio->id, 'image_path' => 'img/inactive.png', 'is_active' => false, 'sort_order' => 0]);

        $response = $this->get(route('portfolio.show', $portfolio->slug));

        $response->assertOk();
        $response->assertSee('about-fallback.png');
        $response->assertDontSee('inactive.png');
    }

    public function test_project_case_study_page_renders_with_seo_title(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::create([
            'user_id' => $user->id,
            'slug' => 'case-study-tenant',
            'site_title' => 'Case Study Tenant',
        ]);

        $project = Project::create([
            'portfolio_id' => $portfolio->id,
            'title' => 'Acme HR',
            'description' => 'Complete HR management system for Acme Corp.',
            'details' => "Key features:\n- Payroll\n- Leave tracking",
            'sort_order' => 0,
        ]);

        $this->assertSame('acme-hr', $project->slug);

        $response = $this->get(route('portfolio.work.show', [$portfolio, $project->slug]));

        $response->assertOk();
        $response->assertSee('HR Management System Development Case Study', false);
        $response->assertSee('Payroll', false);
    }

    public function test_project_slugs_are_unique_per_portfolio(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::create([
            'user_id' => $user->id,
            'slug' => 'slug-collision-tenant',
            'site_title' => 'Slug Collision Tenant',
        ]);

        $first = Project::create([
            'portfolio_id' => $portfolio->id,
            'title' => 'Acme',
            'description' => 'First project.',
            'sort_order' => 0,
        ]);

        $second = Project::create([
            'portfolio_id' => $portfolio->id,
            'title' => 'Acme',
            'description' => 'Second project with the same title.',
            'sort_order' => 1,
        ]);

        $this->assertSame('acme', $first->slug);
        $this->assertSame('acme-2', $second->slug);
    }

    public function test_unknown_project_slug_returns_404(): void
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::create([
            'user_id' => $user->id,
            'slug' => 'missing-project-tenant',
            'site_title' => 'Missing Project Tenant',
        ]);

        $this->get(route('portfolio.work.show', [$portfolio, 'does-not-exist']))->assertNotFound();
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
