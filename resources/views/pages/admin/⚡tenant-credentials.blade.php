<?php

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

new class extends Component {
    public User $tenant;

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(User $user): void
    {
        abort_if($user->isAdmin(), 404);

        $this->tenant = $user;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $this->tenant->forceFill([
            'password' => $validated['password'],
            'remember_token' => Str::random(60),
        ])->save();

        $this->reset('password', 'password_confirmation');
        $this->dispatch('tenant-password-updated');
    }
}; ?>

<section class="w-full">
    <flux:heading size="xl">{{ __('Set Tenant Password') }}</flux:heading>
    <flux:subheading>
        {{ __('Set a new login password for :name (:email). Existing passwords cannot be viewed.', ['name' => $tenant->name, 'email' => $tenant->email]) }}
    </flux:subheading>

    <form wire:submit="save" class="my-6 max-w-lg space-y-6">
        <flux:input wire:model="password" :label="__('New Password')" type="password"
            autocomplete="new-password" required viewable />
        <flux:input wire:model="password_confirmation" :label="__('Confirm New Password')" type="password"
            autocomplete="new-password" required viewable />

        <div class="flex items-center gap-4">
            <flux:button variant="primary" type="submit">
                {{ __('Set Password') }}
            </flux:button>
            <flux:button variant="subtle" :href="route('admin.tenants')" wire:navigate>
                {{ __('Back') }}
            </flux:button>

            <x-action-message on="tenant-password-updated">
                {{ __('Password updated.') }}
            </x-action-message>
        </div>
    </form>
</section>
