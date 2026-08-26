<?php

use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component {
    public string $name = '';

    public string $email = '';

    public string $slug = '';

    public string $site_title = '';

    public function save(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'slug' => 'required|string|max:255|alpha_dash|unique:portfolios,slug',
            'site_title' => 'required|string|max:60',
        ]);

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make(Str::random(40)),
            ]);

            Portfolio::create([
                'user_id' => $user->id,
                'slug' => $validated['slug'],
                'site_title' => $validated['site_title'],
            ]);

            Password::sendResetLink(['email' => $user->email]);
        });

        $this->redirect(route('admin.tenants'), navigate: true);
    }
}; ?>

<section class="w-full">
    <flux:heading size="xl">{{ __('New Tenant') }}</flux:heading>
    <flux:subheading>{{ __('Creates the account and portfolio, then emails the tenant a link to set their own password') }}</flux:subheading>

    <form wire:submit="save" class="my-6 max-w-lg space-y-6">
        <flux:input wire:model="name" :label="__('Name')" type="text" required />
        <flux:input wire:model="email" :label="__('Email')" type="email" required />
        <flux:input wire:model="slug" :label="__('Portfolio Slug')" type="text" required
            :description="__('Used as the fallback path — e.g. /portfolio/:slug — before a custom domain is verified', ['slug' => $slug ?: 'their-slug'])" />
        <flux:input wire:model="site_title" :label="__('Site Title')" type="text" required />

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
