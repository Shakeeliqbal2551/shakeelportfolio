<?php

use App\Models\Education;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component {
    public int $portfolioId;

    public ?int $editingId = null;

    public string $institution = '';
    public string $degree = '';
    public string $date_range = '';
    public ?string $description = null;

    public ?int $deletingId = null;

    public function mount(): void
    {
        $portfolio = Auth::user()->portfolio;

        abort_if(! $portfolio, 404);

        $this->portfolioId = $portfolio->id;
    }

    public function getEducationsProperty()
    {
        return Education::where('portfolio_id', $this->portfolioId)
            ->orderBy('sort_order')
            ->get();
    }

    public function createNew(): void
    {
        $this->resetForm();
        $this->modal('education-form')->show();
    }

    public function edit(int $id): void
    {
        $education = Education::where('portfolio_id', $this->portfolioId)->findOrFail($id);

        $this->editingId = $education->id;
        $this->institution = $education->institution;
        $this->degree = $education->degree;
        $this->date_range = $education->date_range;
        $this->description = $education->description;

        $this->modal('education-form')->show();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'institution' => 'required|string|max:255',
            'degree' => 'required|string|max:255',
            'date_range' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($this->editingId) {
            $education = Education::where('portfolio_id', $this->portfolioId)->findOrFail($this->editingId);
            $education->update($validated);
        } else {
            $maxOrder = Education::where('portfolio_id', $this->portfolioId)->max('sort_order');
            $validated['portfolio_id'] = $this->portfolioId;
            $validated['sort_order'] = $maxOrder === null ? 0 : $maxOrder + 1;
            Education::create($validated);
        }

        $this->modal('education-form')->close();
        $this->resetForm();
        $this->dispatch('education-saved');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->modal('education-delete')->show();
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            Education::where('portfolio_id', $this->portfolioId)->where('id', $this->deletingId)->delete();
        }

        $this->deletingId = null;
        $this->modal('education-delete')->close();
        $this->dispatch('education-saved');
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
        $education = Education::where('portfolio_id', $this->portfolioId)->findOrFail($id);

        $sibling = Education::where('portfolio_id', $this->portfolioId)
            ->when($direction === 'up', fn ($q) => $q->where('sort_order', '<', $education->sort_order)->orderBy('sort_order', 'desc'))
            ->when($direction === 'down', fn ($q) => $q->where('sort_order', '>', $education->sort_order)->orderBy('sort_order', 'asc'))
            ->first();

        if (! $sibling) {
            return;
        }

        DB::transaction(function () use ($education, $sibling) {
            $originalOrder = $education->sort_order;
            $education->update(['sort_order' => $sibling->sort_order]);
            $sibling->update(['sort_order' => $originalOrder]);
        });
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'institution', 'degree', 'date_range', 'description']);
        $this->resetValidation();
    }
}; ?>

<section class="w-full">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Education') }}</flux:heading>
            <flux:subheading>{{ __('Manage your education entries') }}</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="createNew">
            {{ __('Add New') }}
        </flux:button>
    </div>

    <div class="my-6 flex items-center gap-4">
        <x-action-message on="education-saved">{{ __('Saved.') }}</x-action-message>
    </div>

    <div class="space-y-3">
        @forelse ($this->educations as $index => $education)
            <div class="flex items-center justify-between gap-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div>
                    <flux:heading>{{ $education->degree }} &mdash; {{ $education->institution }}</flux:heading>
                    <flux:text class="text-zinc-500">{{ $education->date_range }}</flux:text>
                </div>

                <div class="flex items-center gap-1">
                    <flux:button size="sm" variant="subtle" icon="chevron-up" wire:click="moveUp({{ $education->id }})" :disabled="$index === 0" />
                    <flux:button size="sm" variant="subtle" icon="chevron-down" wire:click="moveDown({{ $education->id }})" :disabled="$index === $this->educations->count() - 1" />
                    <flux:button size="sm" variant="subtle" icon="pencil-square" wire:click="edit({{ $education->id }})" />
                    <flux:button size="sm" variant="subtle" icon="trash" wire:click="confirmDelete({{ $education->id }})" />
                </div>
            </div>
        @empty
            <flux:text class="text-zinc-500">{{ __('No education entries yet.') }}</flux:text>
        @endforelse
    </div>

    <flux:modal name="education-form" class="max-w-lg" @close="resetForm">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">{{ $editingId ? __('Edit Education') : __('Add Education') }}</flux:heading>

            <flux:input wire:model="institution" :label="__('Institution')" type="text" required />
            <flux:input wire:model="degree" :label="__('Degree')" type="text" required />
            <flux:input wire:model="date_range" :label="__('Date Range')" type="text" required placeholder="2016 - 2020" />
            <flux:textarea wire:model="description" :label="__('Description')" rows="4" />

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="education-delete" class="max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete education entry?') }}</flux:heading>
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
