<?php

use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Component;

new class extends Component {
    public string $name = '';

    public string $email = '';

    public string $slug = '';

    public string $site_title = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function save(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'slug' => 'required|string|max:255|alpha_dash|unique:portfolios,slug',
            'site_title' => 'required|string|max:60',
            'password' => ['nullable', 'confirmed', PasswordRule::defaults()],
        ]);

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password'] ?: Str::random(40)),
            ]);

            Portfolio::create([
                'user_id' => $user->id,
                'slug' => $validated['slug'],
                'site_title' => $validated['site_title'],
            ]);

            if (! $validated['password']) {
                Password::sendResetLink(['email' => $user->email]);
            }
        });

        $this->redirect(route('admin.tenants'), navigate: true);
    }
}; ?>

<section class="w-full">
    <flux:heading size="xl">{{ __('New Tenant') }}</flux:heading>
    <flux:subheading>{{ __('Set a password to share securely, or leave it blank to email the tenant a password setup link') }}</flux:subheading>

    <form wire:submit="save" class="my-6 max-w-lg space-y-6">
        <flux:input wire:model="name" :label="__('Name')" type="text" required />
        <flux:input wire:model="email" :label="__('Email')" type="email" required />
        <flux:input wire:model="slug" :label="__('Portfolio Slug')" type="text" required
            :description="__('Used as the fallback path — e.g. /portfolio/:slug — before a custom domain is verified', ['slug' => $slug ?: 'their-slug'])" />
        <flux:input wire:model="site_title" :label="__('Site Title')" type="text" required />
        <flux:input wire:model="password" :label="__('Initial Password (optional)')" type="password"
            autocomplete="new-password" :description="__('Leave blank to send a password setup email')" viewable />
        <flux:input wire:model="password_confirmation" :label="__('Confirm Initial Password')" type="password"
            autocomplete="new-password" viewable />

        <div class="flex items-center gap-4">
            <flux:button variant="primary" type="submit">
                {{ __('Create Tenant') }}
            </flux:button>
            <flux:button variant="subtle" :href="route('admin.tenants')" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
        </div>
    </form>
</section>
