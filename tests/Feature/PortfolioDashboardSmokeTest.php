<?php

namespace Tests\Feature;

use App\Models\AboutSection;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Portfolio;
use App\Models\Post;
use App\Models\Project;
use App\Models\Service;
use App\Models\Skill;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\VisitorLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortfolioDashboardSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected Portfolio $portfolio;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();

        $this->portfolio = Portfolio::create([
            'user_id' => $user->id,
            'slug' => 'test-slug',
            'site_title' => 'Test Site',
            'hero_reassurance_items' => ['a', 'b', 'c'],
            'hero_stats' => [
                ['label' => 'L1', 'value' => 'V1'],
                ['label' => 'L2', 'value' => 'V2'],
                ['label' => 'L3', 'value' => 'V3'],
                ['label' => 'L4', 'value' => 'V4'],
            ],
        ]);

        AboutSection::create([
            'portfolio_id' => $this->portfolio->id,
            'info_cards' => [
                ['label' => 'Based in', 'value' => 'City', 'subvalue' => ''],
            ],
        ]);

        Experience::create(['portfolio_id' => $this->portfolio->id, 'company' => 'Co', 'role' => 'Dev', 'date_range' => '2020', 'description' => 'Did stuff', 'sort_order' => 0]);
        Education::create(['portfolio_id' => $this->portfolio->id, 'institution' => 'Uni', 'degree' => 'BS', 'date_range' => '2020', 'sort_order' => 0]);
        Skill::create(['portfolio_id' => $this->portfolio->id, 'name' => 'PHP', 'sort_order' => 0]);
        Project::create(['portfolio_id' => $this->portfolio->id, 'title' => 'Proj', 'description' => 'Desc', 'tags' => ['a', 'b'], 'sort_order' => 0]);
        Service::create(['portfolio_id' => $this->portfolio->id, 'title' => 'Svc', 'description' => 'Svc desc', 'sort_order' => 0]);
        Testimonial::create(['portfolio_id' => $this->portfolio->id, 'name' => 'Client', 'role_company' => 'CEO, Acme', 'quote' => 'Great', 'sort_order' => 0]);

        $this->actingAs($user);
    }

    public function test_all_dashboard_pages_render(): void
    {
        foreach ([
            'portfolio.settings',
            'portfolio.about',
            'portfolio.profile-images',
            'portfolio.experiences',
            'portfolio.educations',
            'portfolio.skills',
            'portfolio.projects',
            'portfolio.services',
            'portfolio.testimonials',
            'portfolio.posts',
            'portfolio.posts.create',
            'portfolio.visitors',
        ] as $routeName) {
            $this->get(route($routeName))->assertOk();
        }
    }

    public function test_visitors_dashboard_shows_stats_and_recent_visits(): void
    {
        VisitorLog::create([
            'portfolio_id' => $this->portfolio->id,
            'ip_address' => '203.0.113.50',
            'country' => 'Pakistan',
            'city' => 'Islamabad',
            'region' => 'Islamabad Capital Territory',
            'page_visited' => 'home',
            'browser' => 'Chrome',
            'os' => 'Windows',
            'device_type' => 'Desktop',
            'is_repeat_visitor' => false,
            'visit_time' => now()->subHours(2),
            'duration_seconds' => 125,
        ]);

        VisitorLog::create([
            'portfolio_id' => $this->portfolio->id,
            'ip_address' => '203.0.113.51',
            'country' => 'Pakistan',
            'city' => 'Islamabad',
            'region' => 'Islamabad Capital Territory',
            'page_visited' => 'home',
            'browser' => 'Firefox',
            'os' => 'Linux',
            'device_type' => 'Desktop',
            'is_repeat_visitor' => true,
            'visit_time' => now()->subDay(),
            'duration_seconds' => null,
        ]);

        $response = $this->get(route('portfolio.visitors'));

        $response->assertOk();
        $response->assertSee('Islamabad');
        $response->assertSee('Chrome');
        $response->assertSee('2m 5s');
    }

    public function test_other_user_cannot_see_this_portfolios_visitor_data(): void
    {
        VisitorLog::create([
            'portfolio_id' => $this->portfolio->id,
            'ip_address' => '203.0.113.60',
            'country' => 'Germany',
            'city' => 'Berlin',
            'region' => 'Berlin',
            'page_visited' => 'home',
            'browser' => 'Chrome',
            'os' => 'Windows',
            'device_type' => 'Desktop',
            'is_repeat_visitor' => false,
            'visit_time' => now(),
        ]);

        $otherUser = User::factory()->create();
        Portfolio::create([
            'user_id' => $otherUser->id,
            'slug' => 'other-visitor-slug',
            'site_title' => 'Other Site',
        ]);

        $this->actingAs($otherUser);

        $response = $this->get(route('portfolio.visitors'));

        $response->assertOk();
        $response->assertDontSee('Berlin');
    }

    public function test_post_create_and_edit_pages_render(): void
    {
        $post = Post::create([
            'portfolio_id' => $this->portfolio->id,
            'title' => 'Dashboard Post',
            'slug' => 'dashboard-post',
            'body' => '<p>Body</p>',
        ]);

        $this->get(route('portfolio.posts.edit', $post))->assertOk();
    }

    public function test_post_create_and_publish_round_trip(): void
    {
        \Livewire\Livewire::test('pages::portfolio.posts-edit')
            ->set('title', 'New Post')
            ->set('body', '<p>Hello world</p>')
            ->set('publish_now', true)
            ->call('save')
            ->assertRedirect(route('portfolio.posts'));

        $post = Post::where('title', 'New Post')->first();
        $this->assertNotNull($post);
        $this->assertEquals('new-post', $post->slug);
        $this->assertTrue($post->isPublished());
    }

    public function test_experience_crud_and_reorder(): void
    {
        $second = Experience::create(['portfolio_id' => $this->portfolio->id, 'company' => 'Co2', 'role' => 'Dev2', 'date_range' => '2021', 'description' => 'More stuff', 'sort_order' => 1]);
        $first = Experience::where('company', 'Co')->first();

        \Livewire\Livewire::test('pages::portfolio.experiences')
            ->call('moveDown', $first->id)
            ->assertHasNoErrors();

        $this->assertEquals(1, $first->fresh()->sort_order);
        $this->assertEquals(0, $second->fresh()->sort_order);

        \Livewire\Livewire::test('pages::portfolio.experiences')
            ->set('company', 'NewCo')
            ->set('role', 'NewRole')
            ->set('date_range', '2022')
            ->set('description', 'New description')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('experiences', ['company' => 'NewCo', 'portfolio_id' => $this->portfolio->id]);
    }

    public function test_project_tags_round_trip(): void
    {
        \Livewire\Livewire::test('pages::portfolio.projects')
            ->set('title', 'New Project')
            ->set('description', 'A description')
            ->set('tags', 'Laravel, Vue, Tailwind')
            ->call('save')
            ->assertHasNoErrors();

        $project = Project::where('title', 'New Project')->first();
        $this->assertEquals(['Laravel', 'Vue', 'Tailwind'], $project->tags);
    }

    public function test_about_info_cards_update(): void
    {
        \Livewire\Livewire::test('pages::portfolio.about')
            ->set('info_cards.0.value', 'Updated City')
            ->call('save')
            ->assertHasNoErrors();

        $about = AboutSection::where('portfolio_id', $this->portfolio->id)->first();
        $this->assertEquals('Updated City', $about->info_cards[0]['value']);
    }

    public function test_portfolio_settings_update(): void
    {
        \Livewire\Livewire::test('pages::portfolio.settings')
            ->set('site_title', 'New Title')
            ->set('hero_stats.0.label', 'Years')
            ->set('hero_stats.0.value', '5+')
            ->call('save')
            ->assertHasNoErrors();

        $this->portfolio->refresh();
        $this->assertEquals('New Title', $this->portfolio->site_title);
        $this->assertEquals('Years', $this->portfolio->hero_stats[0]['label']);
    }

    public function test_about_resume_and_profile_image_upload_replaces_old_file(): void
    {
        Storage::fake('public');

        $oldResume = UploadedFile::fake()->create('old.pdf', 10, 'application/pdf');
        $oldPath = $oldResume->store("portfolios/{$this->portfolio->id}/resume", 'public');

        \App\Models\AboutSection::where('portfolio_id', $this->portfolio->id)->update(['resume_path' => $oldPath]);

        $newResume = UploadedFile::fake()->create('resume.pdf', 10, 'application/pdf');
        $profileImage = UploadedFile::fake()->image('profile.jpg');

        \Livewire\Livewire::test('pages::portfolio.about')
            ->set('resume', $newResume)
            ->set('profile_image', $profileImage)
            ->call('save')
            ->assertHasNoErrors();

        Storage::disk('public')->assertMissing($oldPath);

        $about = \App\Models\AboutSection::where('portfolio_id', $this->portfolio->id)->first();
        Storage::disk('public')->assertExists($about->resume_path);
        Storage::disk('public')->assertExists($about->profile_image_path);
    }

    public function test_project_delete_removes_image_file(): void
    {
        Storage::fake('public');

        $image = UploadedFile::fake()->image('project.jpg');
        $path = $image->store("portfolios/{$this->portfolio->id}/projects", 'public');

        $project = Project::create([
            'portfolio_id' => $this->portfolio->id,
            'title' => 'With Image',
            'description' => 'Desc',
            'image_path' => $path,
            'sort_order' => 1,
        ]);

        \Livewire\Livewire::test('pages::portfolio.projects')
            ->call('confirmDelete', $project->id)
            ->call('delete')
            ->assertHasNoErrors();

        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_profile_images_dashboard_page_renders(): void
    {
        $this->get(route('portfolio.profile-images'))->assertOk();
    }

    public function test_profile_image_upload_toggle_and_delete(): void
    {
        Storage::fake('public');

        $image = UploadedFile::fake()->image('profile.jpg');

        \Livewire\Livewire::test('pages::portfolio.profile-images')
            ->set('image', $image)
            ->set('alt_text', 'Gallery photo')
            ->call('save')
            ->assertHasNoErrors();

        $profileImage = \App\Models\ProfileImage::where('portfolio_id', $this->portfolio->id)->first();
        $this->assertNotNull($profileImage);
        $this->assertFalse($profileImage->is_active);
        Storage::disk('public')->assertExists($profileImage->image_path);

        \Livewire\Livewire::test('pages::portfolio.profile-images')
            ->call('toggleActive', $profileImage->id);

        $this->assertTrue($profileImage->fresh()->is_active);

        \Livewire\Livewire::test('pages::portfolio.profile-images')
            ->call('confirmDelete', $profileImage->id)
            ->call('delete')
            ->assertHasNoErrors();

        Storage::disk('public')->assertMissing($profileImage->image_path);
        $this->assertDatabaseMissing('profile_images', ['id' => $profileImage->id]);
    }

    public function test_other_user_cannot_access_this_portfolio_data(): void
    {
        $otherUser = User::factory()->create();
        $otherPortfolio = Portfolio::create([
            'user_id' => $otherUser->id,
            'slug' => 'other-slug',
            'site_title' => 'Other Site',
        ]);

        $this->actingAs($otherUser);

        $component = \Livewire\Livewire::test('pages::portfolio.experiences');

        $this->assertEquals($otherPortfolio->id, $component->get('portfolioId'));
        $this->assertNotEquals($this->portfolio->id, $component->get('portfolioId'));
    }
}
