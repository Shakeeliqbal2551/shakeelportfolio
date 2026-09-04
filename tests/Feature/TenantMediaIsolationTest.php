<?php

namespace Tests\Feature;

use App\Models\AboutSection;
use App\Models\Domain;
use App\Models\Portfolio;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TenantMediaIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_domain_uses_same_origin_media_contact_and_favicon_urls(): void
    {
        $portfolio = $this->tenant('jawad.example', 'jawad', [
            'logo_path' => 'portfolios/1/logo/logo.webp',
            'favicon_path' => 'portfolios/1/favicon/icon.webp',
        ]);

        Project::create([
            'portfolio_id' => $portfolio->id,
            'title' => 'Project One',
            'description' => 'A project',
            'image_path' => "portfolios/{$portfolio->id}/projects/project.webp",
            'sort_order' => 0,
        ]);

        $response = $this->get('http://jawad.example/');

        $response->assertOk();
        $response->assertSee('/portfolio-media/portfolios/1/logo/logo.webp', false);
        $response->assertSee('/portfolio-media/portfolios/1/favicon/icon.webp', false);
        $response->assertSee("/portfolio-media/portfolios/{$portfolio->id}/projects/project.webp", false);
        $response->assertSee("/portfolio/{$portfolio->slug}/contact/send-email", false);
        $response->assertDontSee(config('app.url').'/storage/portfolios', false);
    }

    public function test_custom_domain_can_load_its_media_but_not_another_tenants_media(): void
    {
        Storage::fake('public');

        $jawad = $this->tenant('jawad.example', 'jawad');
        $other = $this->tenant('other.example', 'other');
        $jawadPath = "portfolios/{$jawad->id}/projects/image.webp";
        $otherPath = "portfolios/{$other->id}/projects/image.webp";
        Storage::disk('public')->put($jawadPath, 'jawad image');
        Storage::disk('public')->put($otherPath, 'other image');

        $this->get("http://jawad.example/portfolio-media/{$jawadPath}")->assertOk();
        $this->get("http://jawad.example/portfolio-media/{$otherPath}")->assertNotFound();
    }

    public function test_resume_download_is_scoped_to_the_custom_domain_portfolio(): void
    {
        Storage::fake('public');

        $jawad = $this->tenant('jawad.example', 'jawad');
        $other = $this->tenant('other.example', 'other');

        $jawadPath = "portfolios/{$jawad->id}/resume/resume.pdf";
        $otherPath = "portfolios/{$other->id}/resume/resume.pdf";
        Storage::disk('public')->put($jawadPath, '%PDF jawad');
        Storage::disk('public')->put($otherPath, '%PDF other');

        AboutSection::create(['portfolio_id' => $jawad->id, 'resume_path' => $jawadPath]);
        AboutSection::create(['portfolio_id' => $other->id, 'resume_path' => $otherPath]);

        $this->get("http://jawad.example/portfolio/{$jawad->slug}/resume")
            ->assertOk()
            ->assertDownload('test-user-resume.pdf');

        $this->get("http://jawad.example/portfolio/{$other->slug}/resume")
            ->assertNotFound();
    }

    private function tenant(string $host, string $slug, array $attributes = []): Portfolio
    {
        $user = User::factory()->create(['name' => 'Test User']);
        $portfolio = Portfolio::create(array_merge([
            'user_id' => $user->id,
            'slug' => $slug,
            'site_title' => ucfirst($slug),
            'hero_reassurance_items' => ['a'],
            'hero_stats' => [['label' => 'L1', 'value' => 'V1']],
        ], $attributes));

        Domain::create([
            'portfolio_id' => $portfolio->id,
            'host' => $host,
            'verification_status' => 'verified',
            'verified_at' => now(),
        ]);

        return $portfolio;
    }
}
