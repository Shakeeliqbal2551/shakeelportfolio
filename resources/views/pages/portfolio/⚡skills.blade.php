<?php

use App\Models\Skill;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component {
    public int $portfolioId;

    public ?int $editingId = null;

    public string $name = '';
    public ?string $category = null;
    public ?string $icon = null;

    public ?int $deletingId = null;

    public function mount(): void
    {
        $portfolio = Auth::user()->portfolio;

        abort_if(! $portfolio, 404);

        $this->portfolioId = $portfolio->id;
    }

    public function getSkillsProperty()
    {
        return Skill::where('portfolio_id', $this->portfolioId)
            ->orderBy('sort_order')
            ->get();
    }

    public function createNew(): void
    {
        $this->resetForm();
        $this->modal('skill-form')->show();
    }

    public function edit(int $id): void
    {
        $skill = Skill::where('portfolio_id', $this->portfolioId)->findOrFail($id);

        $this->editingId = $skill->id;
        $this->name = $skill->name;
        $this->category = $skill->category;
        $this->icon = $skill->icon;

        $this->modal('skill-form')->show();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
        ]);

        if ($this->editingId) {
            $skill = Skill::where('portfolio_id', $this->portfolioId)->findOrFail($this->editingId);
            $skill->update($validated);
        } else {
            $maxOrder = Skill::where('portfolio_id', $this->portfolioId)->max('sort_order');
            $validated['portfolio_id'] = $this->portfolioId;
            $validated['sort_order'] = $maxOrder === null ? 0 : $maxOrder + 1;
            Skill::create($validated);
        }

        $this->modal('skill-form')->close();
        $this->resetForm();
        $this->dispatch('skill-saved');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->modal('skill-delete')->show();
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            Skill::where('portfolio_id', $this->portfolioId)->where('id', $this->deletingId)->delete();
        }

        $this->deletingId = null;
        $this->modal('skill-delete')->close();
        $this->dispatch('skill-saved');
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
        $skill = Skill::where('portfolio_id', $this->portfolioId)->findOrFail($id);

        $sibling = Skill::where('portfolio_id', $this->portfolioId)
            ->when($direction === 'up', fn ($q) => $q->where('sort_order', '<', $skill->sort_order)->orderBy('sort_order', 'desc'))
            ->when($direction === 'down', fn ($q) => $q->where('sort_order', '>', $skill->sort_order)->orderBy('sort_order', 'asc'))
            ->first();

        if (! $sibling) {
            return;
        }

        DB::transaction(function () use ($skill, $sibling) {
            $originalOrder = $skill->sort_order;
            $skill->update(['sort_order' => $sibling->sort_order]);
            $sibling->update(['sort_order' => $originalOrder]);
        });
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'category', 'icon']);
        $this->resetValidation();
    }
}; ?>

<section class="w-full">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Skills') }}</flux:heading>
            <flux:subheading>{{ __('Manage your skills') }}</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="createNew">
            {{ __('Add New') }}
        </flux:button>
    </div>

    <div class="my-6 flex items-center gap-4">
        <x-action-message on="skill-saved">{{ __('Saved.') }}</x-action-message>
    </div>

    <div class="space-y-3">
        @forelse ($this->skills as $index => $skill)
            <div class="flex items-center justify-between gap-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div>
                    <flux:heading>{{ $skill->name }}</flux:heading>
                    @if ($skill->category)
                        <flux:text class="text-zinc-500">{{ $skill->category }}</flux:text>
                    @endif
                </div>

                <div class="flex items-center gap-1">
                    <flux:button size="sm" variant="subtle" icon="chevron-up" wire:click="moveUp({{ $skill->id }})" :disabled="$index === 0" />
                    <flux:button size="sm" variant="subtle" icon="chevron-down" wire:click="moveDown({{ $skill->id }})" :disabled="$index === $this->skills->count() - 1" />
                    <flux:button size="sm" variant="subtle" icon="pencil-square" wire:click="edit({{ $skill->id }})" />
                    <flux:button size="sm" variant="subtle" icon="trash" wire:click="confirmDelete({{ $skill->id }})" />
                </div>
            </div>
        @empty
            <flux:text class="text-zinc-500">{{ __('No skills yet.') }}</flux:text>
        @endforelse
    </div>

    <flux:modal name="skill-form" class="max-w-lg" @close="resetForm">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">{{ $editingId ? __('Edit Skill') : __('Add Skill') }}</flux:heading>

            <flux:input wire:model="name" :label="__('Name')" type="text" required />
            <flux:input wire:model="category" :label="__('Category')" type="text" />
            <flux:input wire:model="icon" :label="__('Icon')" type="text" placeholder="e.g. laravel, code-bracket" />

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="skill-delete" class="max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete skill?') }}</flux:heading>
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
