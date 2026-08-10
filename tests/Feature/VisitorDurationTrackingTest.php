<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use App\Models\User;
use App\Models\VisitorLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitorDurationTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected Portfolio $portfolio;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();

        $this->portfolio = Portfolio::create([
            'user_id' => $user->id,
            'slug' => 'duration-test-slug',
            'site_title' => 'Duration Test Site',
        ]);
    }

    public function test_log_visitor_stores_session_token(): void
    {
        $this->get(route('portfolio.contact.log', $this->portfolio->slug).'?page=Home&screen=1920x1080&session_token=abc-123')
            ->assertOk();

        $this->assertDatabaseHas('visitor_logs', [
            'portfolio_id' => $this->portfolio->id,
            'session_token' => 'abc-123',
        ]);
    }

    public function test_record_duration_updates_matching_row_by_session_token(): void
    {
        $log = VisitorLog::create([
            'portfolio_id' => $this->portfolio->id,
            'ip_address' => '203.0.113.20',
            'page_visited' => 'home',
            'is_repeat_visitor' => false,
            'visit_time' => now(),
            'session_token' => 'token-xyz',
            'duration_seconds' => null,
        ]);

        $response = $this->post(route('portfolio.contact.duration', $this->portfolio->slug), [
            'session_token' => 'token-xyz',
            'duration_seconds' => 42,
        ]);

        $response->assertNoContent();

        $this->assertEquals(42, $log->fresh()->duration_seconds);
    }

    public function test_record_duration_does_not_error_on_unmatched_token(): void
    {
        $response = $this->post(route('portfolio.contact.duration', $this->portfolio->slug), [
            'session_token' => 'no-such-token',
            'duration_seconds' => 10,
        ]);

        $response->assertNoContent();
        $this->assertDatabaseMissing('visitor_logs', ['session_token' => 'no-such-token']);
    }

    public function test_record_duration_rejects_out_of_range_duration_without_error(): void
    {
        $log = VisitorLog::create([
            'portfolio_id' => $this->portfolio->id,
            'ip_address' => '203.0.113.21',
            'page_visited' => 'home',
            'is_repeat_visitor' => false,
            'visit_time' => now(),
            'session_token' => 'token-garbage',
            'duration_seconds' => null,
        ]);

        $response = $this->post(route('portfolio.contact.duration', $this->portfolio->slug), [
            'session_token' => 'token-garbage',
            'duration_seconds' => 999999999,
        ]);

        $response->assertNoContent();
        $this->assertNull($log->fresh()->duration_seconds);
    }

    public function test_record_duration_only_updates_row_scoped_to_correct_portfolio(): void
    {
        $otherUser = User::factory()->create();
        $otherPortfolio = Portfolio::create([
            'user_id' => $otherUser->id,
            'slug' => 'duration-other-slug',
            'site_title' => 'Other Duration Site',
        ]);

        $log = VisitorLog::create([
            'portfolio_id' => $otherPortfolio->id,
            'ip_address' => '203.0.113.22',
            'page_visited' => 'home',
            'is_repeat_visitor' => false,
            'visit_time' => now(),
            'session_token' => 'cross-tenant-token',
            'duration_seconds' => null,
        ]);

        // Posting the token under the WRONG portfolio slug must not update the row.
        $this->post(route('portfolio.contact.duration', $this->portfolio->slug), [
            'session_token' => 'cross-tenant-token',
            'duration_seconds' => 77,
        ])->assertNoContent();

        $this->assertNull($log->fresh()->duration_seconds);
    }
}
