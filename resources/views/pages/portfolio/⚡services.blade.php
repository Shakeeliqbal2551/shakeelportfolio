<?php

use App\Models\Service;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component {
    public int $portfolioId;

    public ?int $editingId = null;

    public string $title = '';
    public string $description = '';
    public ?string $icon = null;

    public ?int $deletingId = null;

    public function mount(): void
    {
        $portfolio = Auth::user()->portfolio;

        abort_if(! $portfolio, 404);

        $this->portfolioId = $portfolio->id;
    }

    public function getServicesProperty()
    {
        return Service::where('portfolio_id', $this->portfolioId)
            ->orderBy('sort_order')
            ->get();
    }

    public function createNew(): void
    {
        $this->resetForm();
        $this->modal('service-form')->show();
    }

    public function edit(int $id): void
    {
        $service = Service::where('portfolio_id', $this->portfolioId)->findOrFail($id);

        $this->editingId = $service->id;
        $this->title = $service->title;
        $this->description = $service->description;
        $this->icon = $service->icon;

        $this->modal('service-form')->show();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'icon' => 'nullable|string|max:255',
        ]);

        if ($this->editingId) {
            $service = Service::where('portfolio_id', $this->portfolioId)->findOrFail($this->editingId);
            $service->update($validated);
        } else {
            $maxOrder = Service::where('portfolio_id', $this->portfolioId)->max('sort_order');
            $validated['portfolio_id'] = $this->portfolioId;
            $validated['sort_order'] = $maxOrder === null ? 0 : $maxOrder + 1;
            Service::create($validated);
        }

        $this->modal('service-form')->close();
        $this->resetForm();
        $this->dispatch('service-saved');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->modal('service-delete')->show();
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            Service::where('portfolio_id', $this->portfolioId)->where('id', $this->deletingId)->delete();
        }

        $this->deletingId = null;
        $this->modal('service-delete')->close();
        $this->dispatch('service-saved');
    }

    public function moveUp(int $id): void
    {
        $this->swap($id, 'up');
    }

    public function moveDown(int $id): void
    {
        $this->swap($id, 'down');
    }

    protected function swap(int $id, string $direction): void
    {
        $service = Service::where('portfolio_id', $this->portfolioId)->findOrFail($id);

        $sibling = Service::where('portfolio_id', $this->portfolioId)
            ->when($direction === 'up', fn ($q) => $q->where('sort_order', '<', $service->sort_order)->orderBy('sort_order', 'desc'))
            ->when($direction === 'down', fn ($q) => $q->where('sort_order', '>', $service->sort_order)->orderBy('sort_order', 'asc'))
            ->first();

        if (! $sibling) {
            return;
        }

        DB::transaction(function () use ($service, $sibling) {
            $originalOrder = $service->sort_order;
            $service->update(['sort_order' => $sibling->sort_order]);
            $sibling->update(['sort_order' => $originalOrder]);
        });
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'title', 'description', 'icon']);
        $this->resetValidation();
    }
}; ?>

<section class="w-full">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Services') }}</flux:heading>
            <flux:subheading>{{ __('Manage the services you offer') }}</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="createNew">
            {{ __('Add New') }}
        </flux:button>
    </div>

    <div class="my-6 flex items-center gap-4">
        <x-action-message on="service-saved">{{ __('Saved.') }}</x-action-message>
    </div>

    <div class="space-y-3">
        @forelse ($this->services as $index => $service)
            <div class="flex items-center justify-between gap-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div>
                    <flux:heading>{{ $service->title }}</flux:heading>
                    @if ($service->description)
                        <flux:text class="text-zinc-500">{{ \Illuminate\Support\Str::limit($service->description, 80) }}</flux:text>
                    @endif
                </div>

                <div class="flex items-center gap-1">
                    <flux:button size="sm" variant="subtle" icon="chevron-up" wire:click="moveUp({{ $service->id }})" :disabled="$index === 0" />
                    <flux:button size="sm" variant="subtle" icon="chevron-down" wire:click="moveDown({{ $service->id }})" :disabled="$index === $this->services->count() - 1" />
                    <flux:button size="sm" variant="subtle" icon="pencil-square" wire:click="edit({{ $service->id }})" />
                    <flux:button size="sm" variant="subtle" icon="trash" wire:click="confirmDelete({{ $service->id }})" />
                </div>
            </div>
        @empty
            <flux:text class="text-zinc-500">{{ __('No services yet.') }}</flux:text>
        @endforelse
    </div>

    <flux:modal name="service-form" class="max-w-lg" @close="resetForm">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">{{ $editingId ? __('Edit Service') : __('Add Service') }}</flux:heading>

            <flux:input wire:model="title" :label="__('Title')" type="text" required />
            <flux:textarea wire:model="description" :label="__('Description')" rows="4" required />
            <flux:input wire:model="icon" :label="__('Icon')" type="text" placeholder="e.g. wrench, cog-6-tooth" />

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="service-delete" class="max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete service?') }}</flux:heading>
            <flux:text>{{ __('This action cannot be undone.') }}</flux:text>

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="delete">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
