<?php

use App\Models\Testimonial;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public int $portfolioId;

    public ?int $editingId = null;

    public string $name = '';
    public string $role_company = '';
    public string $quote = '';
    public $avatar = null;
    public ?string $existingAvatarPath = null;

    public ?int $deletingId = null;

    public function mount(): void
    {
        $portfolio = Auth::user()->portfolio;

        abort_if(! $portfolio, 404);

        $this->portfolioId = $portfolio->id;
    }

    public function getTestimonialsProperty()
    {
        return Testimonial::where('portfolio_id', $this->portfolioId)
            ->orderBy('sort_order')
            ->get();
    }

    public function createNew(): void
    {
        $this->resetForm();
        $this->modal('testimonial-form')->show();
    }

    public function edit(int $id): void
    {
        $testimonial = Testimonial::where('portfolio_id', $this->portfolioId)->findOrFail($id);

        $this->editingId = $testimonial->id;
        $this->name = $testimonial->name;
        $this->role_company = $testimonial->role_company;
        $this->quote = $testimonial->quote;
        $this->existingAvatarPath = $testimonial->avatar_path;

        $this->modal('testimonial-form')->show();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'role_company' => 'required|string|max:255',
            'quote' => 'required|string',
            'avatar' => 'nullable|image|max:2048',
        ]);

        unset($validated['avatar']);

        if ($this->avatar) {
            if ($this->existingAvatarPath) {
                Storage::disk('public')->delete($this->existingAvatarPath);
            }

            $validated['avatar_path'] = $this->avatar->store("portfolios/{$this->portfolioId}/testimonials", 'public');
        }

        if ($this->editingId) {
            $testimonial = Testimonial::where('portfolio_id', $this->portfolioId)->findOrFail($this->editingId);
            $testimonial->update($validated);
        } else {
            $maxOrder = Testimonial::where('portfolio_id', $this->portfolioId)->max('sort_order');
            $validated['portfolio_id'] = $this->portfolioId;
            $validated['sort_order'] = $maxOrder === null ? 0 : $maxOrder + 1;
            Testimonial::create($validated);
        }

        $this->modal('testimonial-form')->close();
        $this->resetForm();
        $this->dispatch('testimonial-saved');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->modal('testimonial-delete')->show();
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $testimonial = Testimonial::where('portfolio_id', $this->portfolioId)->where('id', $this->deletingId)->first();

            if ($testimonial) {
                if ($testimonial->avatar_path) {
                    Storage::disk('public')->delete($testimonial->avatar_path);
                }

                $testimonial->delete();
            }
        }

        $this->deletingId = null;
        $this->modal('testimonial-delete')->close();
        $this->dispatch('testimonial-saved');
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
        $testimonial = Testimonial::where('portfolio_id', $this->portfolioId)->findOrFail($id);

        $sibling = Testimonial::where('portfolio_id', $this->portfolioId)
            ->when($direction === 'up', fn ($q) => $q->where('sort_order', '<', $testimonial->sort_order)->orderBy('sort_order', 'desc'))
            ->when($direction === 'down', fn ($q) => $q->where('sort_order', '>', $testimonial->sort_order)->orderBy('sort_order', 'asc'))
            ->first();

        if (! $sibling) {
            return;
        }

        DB::transaction(function () use ($testimonial, $sibling) {
            $originalOrder = $testimonial->sort_order;
            $testimonial->update(['sort_order' => $sibling->sort_order]);
            $sibling->update(['sort_order' => $originalOrder]);
        });
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'role_company', 'quote', 'avatar', 'existingAvatarPath']);
        $this->resetValidation();
    }
}; ?>

<section class="w-full">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Testimonials') }}</flux:heading>
            <flux:subheading>{{ __('Manage client testimonials') }}</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="createNew">
            {{ __('Add New') }}
        </flux:button>
    </div>

    <div class="my-6 flex items-center gap-4">
        <x-action-message on="testimonial-saved">{{ __('Saved.') }}</x-action-message>
    </div>

    <div class="space-y-3">
        @forelse ($this->testimonials as $index => $testimonial)
            <div class="flex items-center justify-between gap-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="flex items-center gap-3">
                    @if ($testimonial->avatar_path)
                        <img src="{{ $testimonial->avatar_url }}" alt="{{ $testimonial->name }}" class="h-10 w-10 rounded-full object-cover">
                    @endif
                    <div>
                        <flux:heading>{{ $testimonial->name }}</flux:heading>
                        @if ($testimonial->role_company)
                            <flux:text class="text-zinc-500">{{ $testimonial->role_company }}</flux:text>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-1">
                    <flux:button size="sm" variant="subtle" icon="chevron-up" wire:click="moveUp({{ $testimonial->id }})" :disabled="$index === 0" />
                    <flux:button size="sm" variant="subtle" icon="chevron-down" wire:click="moveDown({{ $testimonial->id }})" :disabled="$index === $this->testimonials->count() - 1" />
                    <flux:button size="sm" variant="subtle" icon="pencil-square" wire:click="edit({{ $testimonial->id }})" />
                    <flux:button size="sm" variant="subtle" icon="trash" wire:click="confirmDelete({{ $testimonial->id }})" />
                </div>
            </div>
        @empty
            <flux:text class="text-zinc-500">{{ __('No testimonials yet.') }}</flux:text>
        @endforelse
    </div>

    <flux:modal name="testimonial-form" class="max-w-lg" @close="resetForm">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">{{ $editingId ? __('Edit Testimonial') : __('Add Testimonial') }}</flux:heading>

            <flux:input wire:model="name" :label="__('Name')" type="text" required />
            <flux:input wire:model="role_company" :label="__('Role / Company')" type="text" required />
            <flux:textarea wire:model="quote" :label="__('Quote')" rows="4" required />

            <div>
                <flux:input wire:model="avatar" :label="__('Avatar')" type="file" accept="image/*" />
                @if ($existingAvatarPath)
                    <img src="{{ \App\Models\Testimonial::resolveFileUrl($existingAvatarPath) }}" alt="{{ __('Current avatar') }}" class="mt-2 h-16 w-16 rounded-full object-cover">
                @endif
            </div>

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="testimonial-delete" class="max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete testimonial?') }}</flux:heading>
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
