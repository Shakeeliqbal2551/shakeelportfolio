<?php

namespace Tests\Feature\Admin;

use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_gets_404_on_admin_tenants(): void
    {
        $user = User::factory()->create();
        Portfolio::create([
            'user_id' => $user->id,
            'slug' => 'regular-tenant',
            'site_title' => 'Regular Tenant',
        ]);

        $this->actingAs($user)->get(route('admin.tenants'))->assertNotFound();
    }

    public function test_guest_gets_redirected_from_admin_tenants(): void
    {
        $this->get(route('admin.tenants'))->assertRedirect(route('login'));
    }

    public function test_admin_can_view_tenants_list(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get(route('admin.tenants'))->assertOk();
    }

    public function test_admin_can_create_tenant_end_to_end(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.tenants.create'))
            ->assertOk();

        \Livewire\Livewire::actingAs($admin)
            ->test('pages::admin.tenants-create')
            ->set('name', 'New Developer')
            ->set('email', 'new-developer@example.com')
            ->set('slug', 'new-developer')
            ->set('site_title', 'New Developer — Portfolio')
            ->call('save');

        $this->assertDatabaseHas('users', ['email' => 'new-developer@example.com']);
        $this->assertDatabaseHas('portfolios', ['slug' => 'new-developer']);
    }
}
