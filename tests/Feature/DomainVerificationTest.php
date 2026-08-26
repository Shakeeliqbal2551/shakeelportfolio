<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\Portfolio;
use App\Models\User;
use App\Services\DnsResolver;
use App\Services\DomainVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class DomainVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function domain(string $token): Domain
    {
        $user = User::factory()->create();
        $portfolio = Portfolio::create([
            'user_id' => $user->id,
            'slug' => 'verify-tenant',
            'site_title' => 'Verify Tenant',
        ]);

        return Domain::create([
            'portfolio_id' => $portfolio->id,
            'host' => 'verify.example',
            'verification_token' => $token,
        ]);
    }

    public function test_matching_txt_record_marks_domain_verified(): void
    {
        $domain = $this->domain('secret-token');

        $dns = Mockery::mock(DnsResolver::class);
        $dns->shouldReceive('txtRecords')
            ->with('_portfolio-verify.verify.example')
            ->andReturn(['secret-token']);

        $service = new DomainVerificationService($dns);

        $this->assertTrue($service->verify($domain));
        $this->assertSame('verified', $domain->fresh()->verification_status);
        $this->assertNotNull($domain->fresh()->verified_at);
    }

    public function test_missing_txt_record_marks_domain_failed(): void
    {
        $domain = $this->domain('secret-token');

        $dns = Mockery::mock(DnsResolver::class);
        $dns->shouldReceive('txtRecords')->andReturn([]);

        $service = new DomainVerificationService($dns);

        $this->assertFalse($service->verify($domain));
        $this->assertSame('failed', $domain->fresh()->verification_status);
    }
}
