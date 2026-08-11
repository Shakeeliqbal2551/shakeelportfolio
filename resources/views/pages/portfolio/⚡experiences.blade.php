<?php

use App\Models\Experience;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component {
    public int $portfolioId;

    public ?int $editingId = null;

    public string $company = '';
    public string $role = '';
    public ?string $project_name = null;
    public string $date_range = '';
    public string $description = '';

    public ?int $deletingId = null;

    public function mount(): void
    {
        $portfolio = Auth::user()->portfolio;

        abort_if(! $portfolio, 404);

        $this->portfolioId = $portfolio->id;
    }

    public function getExperiencesProperty()
    {
        return Experience::where('portfolio_id', $this->portfolioId)
            ->orderBy('sort_order')
            ->get();
    }

    public function createNew(): void
    {
        $this->resetForm();
        $this->modal('experience-form')->show();
    }

    public function edit(int $id): void
    {
        $experience = Experience::where('portfolio_id', $this->portfolioId)->findOrFail($id);

        $this->editingId = $experience->id;
        $this->company = $experience->company;
        $this->role = $experience->role;
        $this->project_name = $experience->project_name;
        $this->date_range = $experience->date_range;
        $this->description = $experience->description;

        $this->modal('experience-form')->show();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'company' => 'required|string|max:255',
            'role' => 'required|string|max:255',
            'project_name' => 'nullable|string|max:255',
            'date_range' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        if ($this->editingId) {
            $experience = Experience::where('portfolio_id', $this->portfolioId)->findOrFail($this->editingId);
            $experience->update($validated);
        } else {
            $maxOrder = Experience::where('portfolio_id', $this->portfolioId)->max('sort_order');
            $validated['portfolio_id'] = $this->portfolioId;
            $validated['sort_order'] = $maxOrder === null ? 0 : $maxOrder + 1;
            Experience::create($validated);
        }

        $this->modal('experience-form')->close();
        $this->resetForm();
        $this->dispatch('experience-saved');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->modal('experience-delete')->show();
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            Experience::where('portfolio_id', $this->portfolioId)->where('id', $this->deletingId)->delete();
        }

        $this->deletingId = null;
        $this->modal('experience-delete')->close();
        $this->dispatch('experience-saved');
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
        $experience = Experience::where('portfolio_id', $this->portfolioId)->findOrFail($id);

        $sibling = Experience::where('portfolio_id', $this->portfolioId)
            ->when($direction === 'up', fn ($q) => $q->where('sort_order', '<', $experience->sort_order)->orderBy('sort_order', 'desc'))
            ->when($direction === 'down', fn ($q) => $q->where('sort_order', '>', $experience->sort_order)->orderBy('sort_order', 'asc'))
            ->first();

        if (! $sibling) {
            return;
        }

        DB::transaction(function () use ($experience, $sibling) {
            $originalOrder = $experience->sort_order;
            $experience->update(['sort_order' => $sibling->sort_order]);
            $sibling->update(['sort_order' => $originalOrder]);
        });
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'company', 'role', 'project_name', 'date_range', 'description']);
        $this->resetValidation();
    }
}; ?>

<section class="w-full">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Experience') }}</flux:heading>
            <flux:subheading>{{ __('Manage your work experience entries') }}</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="createNew">
            {{ __('Add New') }}
        </flux:button>
    </div>

    <div class="my-6 flex items-center gap-4">
        <x-action-message on="experience-saved">{{ __('Saved.') }}</x-action-message>
    </div>

    <div class="space-y-3">
        @forelse ($this->experiences as $index => $experience)
            <div class="flex items-center justify-between gap-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div>
                    <flux:heading>{{ $experience->role }} &mdash; {{ $experience->company }}</flux:heading>
                    <flux:text class="text-zinc-500">{{ $experience->date_range }}</flux:text>
                    @if ($experience->project_name)
                        <flux:text class="block">{{ $experience->project_name }}</flux:text>
                    @endif
                </div>

                <div class="flex items-center gap-1">
                    <flux:button size="sm" variant="subtle" icon="chevron-up" wire:click="moveUp({{ $experience->id }})" :disabled="$index === 0" />
                    <flux:button size="sm" variant="subtle" icon="chevron-down" wire:click="moveDown({{ $experience->id }})" :disabled="$index === $this->experiences->count() - 1" />
                    <flux:button size="sm" variant="subtle" icon="pencil-square" wire:click="edit({{ $experience->id }})" />
                    <flux:button size="sm" variant="subtle" icon="trash" wire:click="confirmDelete({{ $experience->id }})" />
                </div>
            </div>
        @empty
            <flux:text class="text-zinc-500">{{ __('No experience entries yet.') }}</flux:text>
        @endforelse
    </div>

    <flux:modal name="experience-form" class="max-w-lg" @close="resetForm">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">{{ $editingId ? __('Edit Experience') : __('Add Experience') }}</flux:heading>

            <flux:input wire:model="company" :label="__('Company')" type="text" required />
            <flux:input wire:model="role" :label="__('Role')" type="text" required />
            <flux:input wire:model="project_name" :label="__('Project Name')" type="text" />
            <flux:input wire:model="date_range" :label="__('Date Range')" type="text" required placeholder="Jan 2022 - Present" />
            <flux:textarea wire:model="description" :label="__('Description')" rows="4" required />

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="experience-delete" class="max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete experience entry?') }}</flux:heading>
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
