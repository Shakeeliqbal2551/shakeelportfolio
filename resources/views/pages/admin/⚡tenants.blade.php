<?php

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public function getTenantsProperty()
    {
        return User::with(['portfolio.domains'])
            ->orderBy('name')
            ->paginate(20);
    }
}; ?>

<section class="w-full">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Tenants') }}</flux:heading>
            <flux:subheading>{{ __('Every developer using the platform, and the domains connected to their portfolio') }}</flux:subheading>
        </div>

        <flux:button variant="primary" :href="route('admin.tenants.create')" wire:navigate>
            {{ __('New Tenant') }}
        </flux:button>
    </div>

    <div class="dash-table-wrap my-6">
        <table class="w-full text-left text-sm">
            <thead class="dash-table-head">
                <tr>
                    <th class="px-4 py-2 font-medium text-zinc-500">{{ __('Name') }}</th>
                    <th class="px-4 py-2 font-medium text-zinc-500">{{ __('Email') }}</th>
                    <th class="px-4 py-2 font-medium text-zinc-500">{{ __('Portfolio Slug') }}</th>
                    <th class="px-4 py-2 font-medium text-zinc-500">{{ __('Domains') }}</th>
                    <th class="px-4 py-2 font-medium text-zinc-500">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="dash-row-divide">
                @forelse ($this->tenants as $tenant)
                    <tr>
                        <td class="px-4 py-3">
                            {{ $tenant->name }}
                            @if ($tenant->isAdmin())
                                <flux:badge size="sm" color="blue">{{ __('Admin') }}</flux:badge>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $tenant->email }}</td>
                        <td class="px-4 py-3">{{ $tenant->portfolio?->slug ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @forelse ($tenant->portfolio?->domains ?? [] as $domain)
                                <flux:badge size="sm" :color="$domain->isVerified() ? 'green' : 'zinc'">
                                    {{ $domain->host }}
                                </flux:badge>
                            @empty
                                <span class="text-zinc-500">{{ __('No domains') }}</span>
                            @endforelse
                        </td>
                        <td class="px-4 py-3">
                            @unless ($tenant->isAdmin())
                                <flux:button size="sm" variant="subtle" :href="route('admin.tenants.credentials', $tenant)" wire:navigate>
                                    {{ __('Set Password') }}
                                </flux:button>
                            @endunless
                            @if ($tenant->portfolio)
                                <flux:button size="sm" variant="subtle" :href="route('admin.tenants.domains', $tenant)" wire:navigate>
                                    {{ __('Manage Domains') }}
                                </flux:button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-6 text-zinc-500" colspan="5">{{ __('No tenants yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $this->tenants->links() }}
</section>
