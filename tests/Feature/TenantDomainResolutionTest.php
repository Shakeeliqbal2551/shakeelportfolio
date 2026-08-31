<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantDomainResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_custom_domain_resolves_to_its_own_portfolio(): void
    {
        $defaultUser = User::factory()->create();
        Portfolio::create([
            'user_id' => $defaultUser->id,
            'slug' => 'shakeel-iqbal-cheema',
            'site_title' => 'Default Portfolio',
            'hero_reassurance_items' => ['a'],
            'hero_stats' => [['label' => 'L1', 'value' => 'V1']],
        ]);

        $tenantUser = User::factory()->create(['name' => 'Jane Developer']);
        $tenantPortfolio = Portfolio::create([
            'user_id' => $tenantUser->id,
            'slug' => 'jane-developer',
            'site_title' => 'Jane Developer — Portfolio',
            'hero_reassurance_items' => ['a'],
            'hero_stats' => [['label' => 'L1', 'value' => 'V1']],
        ]);

        Domain::create([
            'portfolio_id' => $tenantPortfolio->id,
            'host' => 'jane.example',
            'verification_status' => 'verified',
            'verified_at' => now(),
        ]);

        $response = $this->get('http://jane.example/');

        $response->assertOk();
        $response->assertSee('Jane Developer — Portfolio');
    }

    public function test_pending_domain_does_not_resolve(): void
    {
        $tenantUser = User::factory()->create();
        $tenantPortfolio = Portfolio::create([
            'user_id' => $tenantUser->id,
            'slug' => 'pending-tenant',
            'site_title' => 'Pending Tenant',
        ]);

        Domain::create([
            'portfolio_id' => $tenantPortfolio->id,
            'host' => 'pending.example',
            'verification_status' => 'pending',
        ]);

        $this->get('http://pending.example/')->assertNotFound();
    }

    public function test_unregistered_www_host_redirects_to_verified_apex_domain(): void
    {
        $tenantUser = User::factory()->create();
        $tenantPortfolio = Portfolio::create([
            'user_id' => $tenantUser->id,
            'slug' => 'apex-tenant',
            'site_title' => 'Apex Tenant',
        ]);

        Domain::create([
            'portfolio_id' => $tenantPortfolio->id,
            'host' => 'example.com',
            'verification_status' => 'verified',
            'verified_at' => now(),
        ]);

        $this->get('https://www.example.com/work/example?source=www')
            ->assertRedirect('https://example.com/work/example?source=www')
            ->assertStatus(301);
    }

    public function test_explicitly_registered_www_domain_resolves_without_redirecting(): void
    {
        $tenantUser = User::factory()->create();
        $tenantPortfolio = Portfolio::create([
            'user_id' => $tenantUser->id,
            'slug' => 'www-tenant',
            'site_title' => 'WWW Tenant',
        ]);

        Domain::create([
            'portfolio_id' => $tenantPortfolio->id,
            'host' => 'www.example.com',
            'verification_status' => 'verified',
            'verified_at' => now(),
        ]);

        $this->get('https://www.example.com/')
            ->assertOk()
            ->assertSee('WWW Tenant');
    }
}
