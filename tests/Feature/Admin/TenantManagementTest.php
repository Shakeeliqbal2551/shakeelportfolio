<?php

namespace Tests\Feature\Admin;

use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

    public function test_admin_can_create_tenant_with_an_initial_password(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        \Livewire\Livewire::actingAs($admin)
            ->test('pages::admin.tenants-create')
            ->set('name', 'Credentialed Tenant')
            ->set('email', 'credentialed@example.com')
            ->set('slug', 'credentialed-tenant')
            ->set('site_title', 'Credentialed Tenant')
            ->set('password', 'Secure-password-123')
            ->set('password_confirmation', 'Secure-password-123')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('Secure-password-123', User::where('email', 'credentialed@example.com')->firstOrFail()->password));
    }

    public function test_admin_can_set_an_existing_tenant_password(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $tenant = User::factory()->create(['password' => 'old-password']);

        $this->actingAs($admin)
            ->get(route('admin.tenants.credentials', $tenant))
            ->assertOk();

        \Livewire\Livewire::actingAs($admin)
            ->test('pages::admin.tenant-credentials', ['user' => $tenant])
            ->set('password', 'New-secure-password-123')
            ->set('password_confirmation', 'New-secure-password-123')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('tenant-password-updated');

        $this->assertTrue(Hash::check('New-secure-password-123', $tenant->fresh()->password));
    }

    public function test_non_admin_cannot_open_tenant_credentials(): void
    {
        $tenant = User::factory()->create();

        $this->actingAs($tenant)
            ->get(route('admin.tenants.credentials', $tenant))
            ->assertNotFound();
    }
}
