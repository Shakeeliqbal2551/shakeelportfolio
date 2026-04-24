<?php

use App\Models\Experience;
use App\Support\PortfolioContext;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Admin · Experience')]
class extends Component {
    public ?int $editingId = null;
    public string $company = '';
    public string $role = '';
    public string $subtitle = '';
    public string $location = '';
    public string $company_url = '';
    public ?string $start_date = null;
    public ?string $end_date = null;
    public bool $is_current = false;
    public string $description = '';
    public int $sort_order = 0;
    public bool $is_visible = true;

    #[Computed]
    public function items()
    {
        return PortfolioContext::current()
            ?->experiences()
            ->orderBy('sort_order')
            ->get() ?? collect();
    }

    public function rules(): array
    {
        return [
            'company'     => ['required', 'string', 'max:160'],
            'role'        => ['required', 'string', 'max:160'],
            'subtitle'    => ['nullable', 'string', 'max:200'],
            'location'    => ['nullable', 'string', 'max:160'],
            'company_url' => ['nullable', 'url', 'max:255'],
            'start_date'  => ['nullable', 'date'],
            'end_date'    => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_current'  => ['boolean'],
            'description' => ['nullable', 'string'],
            'sort_order'  => ['integer', 'min:0', 'max:9999'],
            'is_visible'  => ['boolean'],
        ];
    }

    public function startCreate(): void
    {
        $this->reset(['editingId', 'company', 'role', 'subtitle', 'location', 'company_url', 'start_date', 'end_date', 'is_current', 'description']);
        $this->sort_order = (int) ((PortfolioContext::current()?->experiences()->max('sort_order') ?? 0) + 10);
        $this->is_visible = true;
        \Flux\Flux::modal('experience-editor')->show();
    }

    public function startEdit(int $id): void
    {
        $e = Experience::findOrFail($id);
        $this->editingId   = $e->id;
        $this->company     = $e->company;
        $this->role        = $e->role;
        $this->subtitle    = $e->subtitle ?? '';
        $this->location    = $e->location ?? '';
        $this->company_url = $e->company_url ?? '';
        $this->start_date  = $e->start_date?->format('Y-m-d');
        $this->end_date    = $e->end_date?->format('Y-m-d');
        $this->is_current  = (bool) $e->is_current;
        $this->description = $e->description ?? '';
        $this->sort_order  = $e->sort_order;
        $this->is_visible  = (bool) $e->is_visible;
        \Flux\Flux::modal('experience-editor')->show();
    }

    public function save(): void
    {
        $data = $this->validate();
        $portfolio = PortfolioContext::current();

        if ($this->editingId) {
            Experience::where('id', $this->editingId)->update($data);
        } else {
            Experience::create($data + ['portfolio_id' => $portfolio->id]);
        }

        \Flux\Flux::modal('experience-editor')->close();
        \Flux\Flux::toast(heading: __('Saved'), text: __('Experience saved.'), variant: 'success');
        $this->reset(['editingId']);
    }

    public function toggleVisible(int $id): void
    {
        $e = Experience::findOrFail($id);
        $e->update(['is_visible' => ! $e->is_visible]);
    }

    public function delete(int $id): void
    {
        Experience::findOrFail($id)->delete();
        \Flux\Flux::toast(heading: __('Deleted'), text: __('Experience removed.'), variant: 'success');
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-2">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Experience') }}</flux:heading>
            <flux:subheading>{{ __('Work history shown in the About → Experience tab.') }}</flux:subheading>
        </div>
        <flux:button variant="primary" wire:click="startCreate" icon="plus">{{ __('New Entry') }}</flux:button>
    </div>

    <div class="space-y-3">
        @forelse ($this->items as $e)
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900" wire:key="exp-{{ $e->id }}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <flux:heading size="md">{{ $e->role }}</flux:heading>
                            @if ($e->is_current) <flux:badge color="emerald" size="sm">{{ __('Current') }}</flux:badge> @endif
                            @if (! $e->is_visible) <flux:badge color="zinc" size="sm">{{ __('Hidden') }}</flux:badge> @endif
                        </div>
                        <flux:text class="mt-0.5 font-medium text-emerald-600 dark:text-emerald-400">{{ $e->company }}</flux:text>
                        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-zinc-500">
                            @if ($e->subtitle) <span>{{ $e->subtitle }}</span> @endif
                            @if ($e->location) <span>· {{ $e->location }}</span> @endif
                            <span>·
                                {{ $e->start_date?->format('M Y') ?? '?' }}
                                {{ ' – ' }}
                                {{ $e->is_current ? __('Present') : ($e->end_date?->format('M Y') ?? '?') }}
                            </span>
                            <span>· #{{ $e->sort_order }}</span>
                        </div>
                        @if ($e->description)
                            <p class="mt-3 line-clamp-3 text-sm text-zinc-600 dark:text-zinc-400">{{ $e->description }}</p>
                        @endif
                    </div>
                    <div class="flex shrink-0 gap-1">
                        <flux:button size="sm" variant="ghost" :icon="$e->is_visible ? 'eye-slash' : 'eye'" wire:click="toggleVisible({{ $e->id }})" />
                        <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="startEdit({{ $e->id }})" />
                        <flux:button size="sm" variant="danger" icon="trash" wire:click="delete({{ $e->id }})" wire:confirm="{{ __('Delete this entry?') }}" />
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-zinc-300 p-12 text-center text-zinc-500 dark:border-zinc-700">
                {{ __('No experience entries yet.') }}
            </div>
        @endforelse
    </div>

    <flux:modal name="experience-editor" class="md:w-[640px]">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? __('Edit experience') : __('New experience') }}</flux:heading>
            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="company"     label="{{ __('Company') }}" required />
                <flux:input wire:model="role"        label="{{ __('Role') }}" required />
                <flux:input wire:model="subtitle"    label="{{ __('Subtitle / project') }}" placeholder="Remetric Health (US-Based)" class="md:col-span-2" />
                <flux:input wire:model="location"    label="{{ __('Location') }}" placeholder="Remote · Islamabad" />
                <flux:input type="url" wire:model="company_url" label="{{ __('Company URL') }}" />
                <flux:input type="date" wire:model="start_date" label="{{ __('Start date') }}" />
                <flux:input type="date" wire:model="end_date"   label="{{ __('End date') }}" :disabled="$is_current" />
            </div>
            <flux:switch wire:model.live="is_current" label="{{ __('I currently work here') }}" />
            <flux:textarea wire:model="description" label="{{ __('Description') }}" rows="5" />
            <div class="grid grid-cols-2 gap-3">
                <flux:input type="number" wire:model="sort_order" label="{{ __('Sort order') }}" min="0" />
                <div class="flex items-end">
                    <flux:switch wire:model="is_visible" label="{{ __('Visible on site') }}" />
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
