<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use App\Models\User;
use App\Models\VisitorLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VisitorManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Portfolio $portfolio;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();

        $this->portfolio = Portfolio::create([
            'user_id' => $user->id,
            'slug' => 'visitor-mgmt-slug',
            'site_title' => 'Visitor Management Test Site',
        ]);

        $this->actingAs($user);
    }

    private function makeVisit(array $overrides = []): VisitorLog
    {
        return VisitorLog::create(array_merge([
            'portfolio_id' => $this->portfolio->id,
            'ip_address' => '203.0.113.10',
            'country' => 'Pakistan',
            'city' => 'Karachi',
            'region' => 'Sindh',
            'page_visited' => 'home',
            'browser' => 'Chrome',
            'os' => 'Windows',
            'device_type' => 'Desktop',
            'is_repeat_visitor' => false,
            'visit_time' => now(),
        ], $overrides));
    }

    public function test_search_filters_visits_by_ip(): void
    {
        $this->makeVisit(['ip_address' => '198.51.100.7', 'city' => 'Lahore']);
        $this->makeVisit(['ip_address' => '203.0.113.99', 'city' => 'Karachi']);

        Livewire::test('pages::portfolio.visitors')
            ->set('search', '198.51.100.7')
            ->assertSee('198.51.100.7')
            ->assertDontSee('203.0.113.99');
    }

    public function test_search_filters_visits_by_city(): void
    {
        $this->makeVisit(['ip_address' => '198.51.100.7', 'city' => 'Lahore']);
        $this->makeVisit(['ip_address' => '203.0.113.99', 'city' => 'Karachi']);

        Livewire::test('pages::portfolio.visitors')
            ->set('search', 'Lahore')
            ->assertSee('198.51.100.7')
            ->assertDontSee('203.0.113.99');
    }

    public function test_search_resets_pagination_to_first_page(): void
    {
        for ($i = 0; $i < 25; $i++) {
            $this->makeVisit(['ip_address' => "203.0.113.{$i}"]);
        }

        $component = Livewire::withQueryParams(['page' => 2])
            ->test('pages::portfolio.visitors');

        $this->assertEquals(2, $component->instance()->getPage());

        $component->set('search', 'anything');

        $this->assertEquals(1, $component->instance()->getPage());
    }

    public function test_single_row_delete_removes_only_that_row(): void
    {
        $keep = $this->makeVisit(['ip_address' => '203.0.113.10']);
        $delete = $this->makeVisit(['ip_address' => '203.0.113.11']);

        Livewire::test('pages::portfolio.visitors')
            ->call('confirmDelete', $delete->id)
            ->call('delete');

        $this->assertModelMissing($delete);
        $this->assertModelExists($keep);
    }

    public function test_single_row_delete_is_scoped_to_owning_portfolio(): void
    {
        $otherUser = User::factory()->create();
        $otherPortfolio = Portfolio::create([
            'user_id' => $otherUser->id,
            'slug' => 'other-visitor-mgmt-slug',
            'site_title' => 'Other Site',
        ]);

        $otherVisit = VisitorLog::create([
            'portfolio_id' => $otherPortfolio->id,
            'ip_address' => '203.0.113.12',
            'page_visited' => 'home',
            'is_repeat_visitor' => false,
            'visit_time' => now(),
        ]);

        Livewire::test('pages::portfolio.visitors')
            ->call('confirmDelete', $otherVisit->id)
            ->call('delete');

        $this->assertModelExists($otherVisit);
    }

    public function test_delete_by_ip_removes_all_matching_rows_for_that_ip(): void
    {
        $this->makeVisit(['ip_address' => '203.0.113.20']);
        $this->makeVisit(['ip_address' => '203.0.113.20']);
        $unrelated = $this->makeVisit(['ip_address' => '203.0.113.21']);

        Livewire::test('pages::portfolio.visitors')
            ->call('confirmDeleteIp', '203.0.113.20')
            ->assertSet('deletingIpCount', 2)
            ->call('deleteIp');

        $this->assertDatabaseMissing('visitor_logs', [
            'portfolio_id' => $this->portfolio->id,
            'ip_address' => '203.0.113.20',
        ]);
        $this->assertModelExists($unrelated);
    }

    public function test_delete_by_ip_does_not_touch_another_portfolios_rows_with_same_ip(): void
    {
        $otherUser = User::factory()->create();
        $otherPortfolio = Portfolio::create([
            'user_id' => $otherUser->id,
            'slug' => 'other-ip-scope-slug',
            'site_title' => 'Other IP Scope Site',
        ]);

        $sharedIp = '203.0.113.30';

        $this->makeVisit(['ip_address' => $sharedIp]);

        $otherVisit = VisitorLog::create([
            'portfolio_id' => $otherPortfolio->id,
            'ip_address' => $sharedIp,
            'page_visited' => 'home',
            'is_repeat_visitor' => false,
            'visit_time' => now(),
        ]);

        Livewire::test('pages::portfolio.visitors')
            ->call('confirmDeleteIp', $sharedIp)
            ->call('deleteIp');

        $this->assertDatabaseMissing('visitor_logs', [
            'portfolio_id' => $this->portfolio->id,
            'ip_address' => $sharedIp,
        ]);
        $this->assertModelExists($otherVisit);
    }

    public function test_referrer_and_isp_are_nullable_and_render_without_error(): void
    {
        $this->makeVisit([
            'ip_address' => '203.0.113.40',
            'referrer' => null,
            'isp' => null,
        ]);

        $response = $this->get(route('portfolio.visitors'));

        $response->assertOk();
        $response->assertSee('Direct');
    }

    public function test_referrer_and_isp_render_when_present(): void
    {
        $this->makeVisit([
            'ip_address' => '203.0.113.41',
            'referrer' => 'https://google.com/search',
            'isp' => 'Acme Broadband',
        ]);

        $response = $this->get(route('portfolio.visitors'));

        $response->assertOk();
        $response->assertSee('google.com', false);
        $response->assertSee('Acme Broadband');
    }

    public function test_date_range_filters_visits(): void
    {
        $inRange = $this->makeVisit(['ip_address' => '203.0.113.50', 'visit_time' => '2026-03-15 10:00:00']);
        $outOfRange = $this->makeVisit(['ip_address' => '203.0.113.51', 'visit_time' => '2026-05-01 10:00:00']);

        Livewire::test('pages::portfolio.visitors')
            ->set('dateFrom', '2026-03-01')
            ->set('dateTo', '2026-03-31')
            ->assertSee('203.0.113.50')
            ->assertDontSee('203.0.113.51');

        $this->assertModelExists($inRange);
        $this->assertModelExists($outOfRange);
    }

    public function test_clear_date_range_resets_filters(): void
    {
        $this->makeVisit(['ip_address' => '203.0.113.52', 'visit_time' => '2026-03-15 10:00:00']);
        $this->makeVisit(['ip_address' => '203.0.113.53', 'visit_time' => '2026-05-01 10:00:00']);

        Livewire::test('pages::portfolio.visitors')
            ->set('dateFrom', '2026-03-01')
            ->set('dateTo', '2026-03-31')
            ->assertDontSee('203.0.113.53')
            ->call('clearDateRange')
            ->assertSee('203.0.113.52')
            ->assertSee('203.0.113.53');
    }

    public function test_per_page_selector_limits_results(): void
    {
        for ($i = 0; $i < 15; $i++) {
            $this->makeVisit(['ip_address' => "203.0.113.{$i}"]);
        }

        $component = Livewire::test('pages::portfolio.visitors')
            ->set('perPage', '10');

        $this->assertEquals(10, $component->instance()->visits->count());
        $this->assertEquals(15, $component->instance()->visits->total());
    }

    public function test_per_page_all_returns_every_row(): void
    {
        for ($i = 0; $i < 15; $i++) {
            $this->makeVisit(['ip_address' => "203.0.113.{$i}"]);
        }

        $component = Livewire::test('pages::portfolio.visitors')
            ->set('perPage', 'all');

        $this->assertEquals(15, $component->instance()->visits->count());
    }
}
