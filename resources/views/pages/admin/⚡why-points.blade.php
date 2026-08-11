<?php

use App\Models\WhyPoint;
use App\Support\PortfolioContext;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Admin · Why Hire Me')]
class extends Component {
    public ?int $editingId = null;
    public string $label = '';
    public string $title = '';
    public string $description = '';
    public string $icon = '';
    public int $sort_order = 0;
    public bool $is_visible = true;

    #[Computed]
    public function items()
    {
        return PortfolioContext::current()
            ?->whyPoints()
            ->orderBy('sort_order')
            ->get() ?? collect();
    }

    public function rules(): array
    {
        return [
            'label'       => ['nullable', 'string', 'max:80'],
            'title'       => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:500'],
            'icon'        => ['nullable', 'string', 'max:60'],
            'sort_order'  => ['integer', 'min:0', 'max:9999'],
            'is_visible'  => ['boolean'],
        ];
    }

    public function startCreate(): void
    {
        $this->reset(['editingId', 'label', 'title', 'description', 'icon']);
        $this->sort_order = (int) ((PortfolioContext::current()?->whyPoints()->max('sort_order') ?? 0) + 10);
        $this->is_visible = true;
        \Flux\Flux::modal('why-editor')->show();
    }

    public function startEdit(int $id): void
    {
        $w = WhyPoint::findOrFail($id);
        $this->editingId   = $w->id;
        $this->label       = $w->label ?? '';
        $this->title       = $w->title;
        $this->description = $w->description;
        $this->icon        = $w->icon ?? '';
        $this->sort_order  = $w->sort_order;
        $this->is_visible  = (bool) $w->is_visible;
        \Flux\Flux::modal('why-editor')->show();
    }

    public function save(): void
    {
        $data = $this->validate();
        $portfolio = PortfolioContext::current();

        if ($this->editingId) {
            WhyPoint::where('id', $this->editingId)->update($data);
        } else {
            WhyPoint::create($data + ['portfolio_id' => $portfolio->id]);
        }

        \Flux\Flux::modal('why-editor')->close();
        \Flux\Flux::toast(heading: __('Saved'), text: __('Point saved.'), variant: 'success');
        $this->reset(['editingId']);
    }

    public function toggleVisible(int $id): void
    {
        $w = WhyPoint::findOrFail($id);
        $w->update(['is_visible' => ! $w->is_visible]);
    }

    public function delete(int $id): void
    {
        WhyPoint::findOrFail($id)->delete();
        \Flux\Flux::toast(heading: __('Deleted'), text: __('Point removed.'), variant: 'success');
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-2">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Why Hire Me') }}</flux:heading>
            <flux:subheading>{{ __('The value-prop cards shown between About and Featured Projects. 4 is the sweet spot.') }}</flux:subheading>
        </div>
        <flux:button variant="primary" wire:click="startCreate" icon="plus">{{ __('New Point') }}</flux:button>
    </div>

    <div class="grid gap-3 md:grid-cols-2">
        @forelse ($this->items as $w)
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900" wire:key="why-{{ $w->id }}">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        @if ($w->label)
                            <div class="mb-1 text-xs font-semibold uppercase tracking-widest text-emerald-500">{{ $w->label }}</div>
                        @endif
                        <div class="flex items-center gap-2">
                            <flux:heading size="md">{{ $w->title }}</flux:heading>
                            @if (! $w->is_visible) <flux:badge size="sm" color="zinc">{{ __('Hidden') }}</flux:badge> @endif
                        </div>
                        <p class="mt-2 line-clamp-3 text-sm text-zinc-600 dark:text-zinc-400">{{ $w->description }}</p>
                    </div>
                    <div class="flex shrink-0 gap-1">
                        <flux:button size="sm" variant="ghost" :icon="$w->is_visible ? 'eye-slash' : 'eye'" wire:click="toggleVisible({{ $w->id }})" />
                        <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="startEdit({{ $w->id }})" />
                        <flux:button size="sm" variant="danger" icon="trash" wire:click="delete({{ $w->id }})" wire:confirm="{{ __('Delete this point?') }}" />
                    </div>
                </div>
            </div>
        @empty
            <div class="md:col-span-2 rounded-xl border border-dashed border-zinc-300 p-12 text-center text-zinc-500 dark:border-zinc-700">
                {{ __('No points yet.') }}
            </div>
        @endforelse
    </div>

    <flux:modal name="why-editor" class="md:w-[560px]">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? __('Edit point') : __('New point') }}</flux:heading>
            <flux:input wire:model="label" label="{{ __('Label (small uppercase)') }}" placeholder="01 — Craft" />
            <flux:input wire:model="title" label="{{ __('Title') }}" placeholder="Senior-level quality" required />
            <flux:textarea wire:model="description" label="{{ __('Description') }}" rows="4" required />
            <div class="grid gap-3 md:grid-cols-3">
                <flux:input wire:model="icon" label="{{ __('Icon') }}" placeholder="sparkles" />
                <flux:input type="number" wire:model="sort_order" label="{{ __('Sort order') }}" min="0" />
                <div class="flex items-end">
                    <flux:switch wire:model="is_visible" label="{{ __('Visible') }}" />
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
