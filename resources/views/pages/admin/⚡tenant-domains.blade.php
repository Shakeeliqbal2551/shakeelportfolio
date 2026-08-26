<?php

use App\Models\Domain;
use App\Models\User;
use App\Services\DomainVerificationService;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component {
    #[Locked]
    public User $user;

    public string $host = '';

    public function mount(User $user): void
    {
        abort_if(! $user->portfolio, 404);

        $this->user = $user;
    }

    public function getDomainsProperty()
    {
        return $this->user->portfolio->domains()->orderByDesc('is_primary')->orderBy('host')->get();
    }

    public function addDomain(): void
    {
        $validated = $this->validate([
            'host' => 'required|string|max:255|unique:domains,host',
        ]);

        $this->user->portfolio->domains()->create([
            'host' => strtolower($validated['host']),
            'verification_token' => Str::random(32),
        ]);

        $this->host = '';
    }

    public function verify(int $domainId, DomainVerificationService $service): void
    {
        $domain = $this->user->portfolio->domains()->findOrFail($domainId);

        $service->verify($domain);
    }

    public function markPrimary(int $domainId): void
    {
        $domain = $this->user->portfolio->domains()->findOrFail($domainId);

        $domain->markPrimary();
    }

    public function markSslIssued(int $domainId): void
    {
        $domain = $this->user->portfolio->domains()->findOrFail($domainId);

        $domain->update(['ssl_status' => 'issued', 'ssl_issued_at' => now()]);
    }

    public function removeDomain(int $domainId): void
    {
        $this->user->portfolio->domains()->where('id', $domainId)->delete();
    }
}; ?>

<section class="w-full">
    <flux:heading size="xl">{{ __('Domains — :name', ['name' => $user->name]) }}</flux:heading>
    <flux:subheading>{{ __('Connect and verify custom domains for this tenant\'s portfolio') }}</flux:subheading>

    <form wire:submit="addDomain" class="my-6 flex max-w-lg items-end gap-3">
        <div class="flex-1">
            <flux:input wire:model="host" :label="__('Domain')" placeholder="example.com" type="text" required />
        </div>
        <flux:button variant="primary" type="submit">{{ __('Add') }}</flux:button>
    </form>

    <div class="space-y-4">
        @forelse ($this->domains as $domain)
            <div class="dash-card">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:heading size="sm">
                            {{ $domain->host }}
                            @if ($domain->is_primary)
                                <flux:badge size="sm" color="blue">{{ __('Primary') }}</flux:badge>
                            @endif
                        </flux:heading>

                        <div class="mt-1 flex items-center gap-2">
                            <flux:badge size="sm" :color="match ($domain->verification_status) {
                                'verified' => 'green',
                                'failed' => 'red',
                                default => 'zinc',
                            }">
                                {{ __('DNS: :status', ['status' => ucfirst($domain->verification_status)]) }}
                            </flux:badge>
                            <flux:badge size="sm" :color="match ($domain->ssl_status) {
                                'issued' => 'green',
                                'failed' => 'red',
                                default => 'zinc',
                            }">
                                {{ __('SSL: :status', ['status' => ucfirst($domain->ssl_status)]) }}
                            </flux:badge>
                        </div>

                        @unless ($domain->isVerified())
                            <flux:text size="sm" class="mt-2 text-zinc-500">
                                {{ __('Ask the tenant to add a TXT record at :name with value :token') }}
                                <br>
                                <code>_portfolio-verify.{{ $domain->host }}</code> → <code>{{ $domain->verification_token }}</code>
                            </flux:text>
                        @endunless
                    </div>

                    <div class="flex items-center gap-2">
                        @unless ($domain->isVerified())
                            <flux:button size="sm" wire:click="verify({{ $domain->id }})">
                                {{ __('Check DNS') }}
                            </flux:button>
                        @endunless

                        @if ($domain->isVerified() && $domain->ssl_status !== 'issued')
                            <flux:button size="sm" wire:click="markSslIssued({{ $domain->id }})">
                                {{ __('Mark SSL Issued') }}
                            </flux:button>
                        @endif

                        @if ($domain->isVerified() && ! $domain->is_primary)
                            <flux:button size="sm" variant="subtle" wire:click="markPrimary({{ $domain->id }})">
                                {{ __('Make Primary') }}
                            </flux:button>
                        @endif

                        <flux:button size="sm" variant="danger" wire:click="removeDomain({{ $domain->id }})"
                            wire:confirm="{{ __('Remove this domain?') }}">
                            {{ __('Remove') }}
                        </flux:button>
                    </div>
                </div>
            </div>
        @empty
            <flux:text class="text-zinc-500">{{ __('No domains connected yet.') }}</flux:text>
        @endforelse
    </div>

    <flux:text size="sm" class="mt-6 text-zinc-500">
        {{ __('SSL note: this app runs on shared hosting without automated certificate provisioning. Once a domain shows "DNS: Verified", add it as an Addon Domain in hPanel and let AutoSSL issue the certificate, then mark it issued above.') }}
    </flux:text>

    <flux:button variant="subtle" class="mt-6" :href="route('admin.tenants')" wire:navigate>
        {{ __('Back to Tenants') }}
    </flux:button>
</section>
