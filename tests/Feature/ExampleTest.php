<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_a_successful_response(): void
    {
        $user = User::factory()->create();

        Portfolio::create([
            'user_id' => $user->id,
            'slug' => 'shakeel-iqbal-cheema',
            'site_title' => 'Default Portfolio',
            'hero_reassurance_items' => ['a'],
            'hero_stats' => [['label' => 'L1', 'value' => 'V1']],
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
    }
}
