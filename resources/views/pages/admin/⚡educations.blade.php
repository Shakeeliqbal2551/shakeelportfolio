<?php

use App\Models\Education;
use App\Support\PortfolioContext;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Admin · Education')]
class extends Component {
    public ?int $editingId = null;
    public string $institution = '';
    public string $degree = '';
    public string $field = '';
    public string $location = '';
    public ?string $start_date = null;
    public ?string $end_date = null;
    public bool $is_current = false;
    public string $grade = '';
    public string $description = '';
    public int $sort_order = 0;
    public bool $is_visible = true;

    #[Computed]
    public function items()
    {
        return PortfolioContext::current()
            ?->educations()
            ->orderBy('sort_order')
            ->get() ?? collect();
    }

    public function rules(): array
    {
        return [
            'institution' => ['required', 'string', 'max:200'],
            'degree'      => ['required', 'string', 'max:160'],
            'field'       => ['nullable', 'string', 'max:160'],
            'location'    => ['nullable', 'string', 'max:160'],
            'start_date'  => ['nullable', 'date'],
            'end_date'    => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_current'  => ['boolean'],
            'grade'       => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string'],
            'sort_order'  => ['integer', 'min:0', 'max:9999'],
            'is_visible'  => ['boolean'],
        ];
    }

    public function startCreate(): void
    {
        $this->reset(['editingId', 'institution', 'degree', 'field', 'location', 'start_date', 'end_date', 'is_current', 'grade', 'description']);
        $this->sort_order = (int) ((PortfolioContext::current()?->educations()->max('sort_order') ?? 0) + 10);
        $this->is_visible = true;
        \Flux\Flux::modal('education-editor')->show();
    }

    public function startEdit(int $id): void
    {
        $e = Education::findOrFail($id);
        $this->editingId   = $e->id;
        $this->institution = $e->institution;
        $this->degree      = $e->degree;
        $this->field       = $e->field ?? '';
        $this->location    = $e->location ?? '';
        $this->start_date  = $e->start_date?->format('Y-m-d');
        $this->end_date    = $e->end_date?->format('Y-m-d');
        $this->is_current  = (bool) $e->is_current;
        $this->grade       = $e->grade ?? '';
        $this->description = $e->description ?? '';
        $this->sort_order  = $e->sort_order;
        $this->is_visible  = (bool) $e->is_visible;
        \Flux\Flux::modal('education-editor')->show();
    }

    public function save(): void
    {
        $data = $this->validate();
        $portfolio = PortfolioContext::current();

        if ($this->editingId) {
            Education::where('id', $this->editingId)->update($data);
        } else {
            Education::create($data + ['portfolio_id' => $portfolio->id]);
        }

        \Flux\Flux::modal('education-editor')->close();
        \Flux\Flux::toast(heading: __('Saved'), text: __('Education saved.'), variant: 'success');
        $this->reset(['editingId']);
    }

    public function toggleVisible(int $id): void
    {
        $e = Education::findOrFail($id);
        $e->update(['is_visible' => ! $e->is_visible]);
    }

    public function delete(int $id): void
    {
        Education::findOrFail($id)->delete();
        \Flux\Flux::toast(heading: __('Deleted'), text: __('Entry removed.'), variant: 'success');
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-2">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Education') }}</flux:heading>
            <flux:subheading>{{ __('Education entries shown in the About → Education tab.') }}</flux:subheading>
        </div>
        <flux:button variant="primary" wire:click="startCreate" icon="plus">{{ __('New Entry') }}</flux:button>
    </div>

    <div class="space-y-3">
        @forelse ($this->items as $e)
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900" wire:key="edu-{{ $e->id }}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <flux:heading size="md">{{ $e->degree }}</flux:heading>
                            @if ($e->is_current) <flux:badge color="emerald" size="sm">{{ __('Current') }}</flux:badge> @endif
                            @if (! $e->is_visible) <flux:badge color="zinc" size="sm">{{ __('Hidden') }}</flux:badge> @endif
                        </div>
                        <flux:text class="mt-0.5 font-medium text-emerald-600 dark:text-emerald-400">{{ $e->institution }}</flux:text>
                        <div class="mt-1 flex flex-wrap items-center gap-x-3 text-xs text-zinc-500">
                            @if ($e->field) <span>{{ $e->field }}</span> @endif
                            @if ($e->location) <span>· {{ $e->location }}</span> @endif
                            @if ($e->grade) <span>· {{ __('Grade') }}: {{ $e->grade }}</span> @endif
                            <span>·
                                {{ $e->start_date?->format('Y') ?? '?' }}
                                {{ ' – ' }}
                                {{ $e->is_current ? __('Present') : ($e->end_date?->format('Y') ?? '?') }}
                            </span>
                        </div>
                        @if ($e->description)
                            <p class="mt-3 line-clamp-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $e->description }}</p>
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
                {{ __('No education entries yet.') }}
            </div>
        @endforelse
    </div>

    <flux:modal name="education-editor" class="md:w-[640px]">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? __('Edit education') : __('New education') }}</flux:heading>
            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="institution" label="{{ __('Institution') }}" required class="md:col-span-2" />
                <flux:input wire:model="degree" label="{{ __('Degree') }}" required />
                <flux:input wire:model="field"  label="{{ __('Field of study') }}" placeholder="Software Engineering" />
                <flux:input wire:model="location" label="{{ __('Location') }}" />
                <flux:input wire:model="grade" label="{{ __('Grade / GPA') }}" />
                <flux:input type="date" wire:model="start_date" label="{{ __('Start date') }}" />
                <flux:input type="date" wire:model="end_date" label="{{ __('End date') }}" :disabled="$is_current" />
            </div>
            <flux:switch wire:model.live="is_current" label="{{ __('Currently studying here') }}" />
            <flux:textarea wire:model="description" label="{{ __('Description') }}" rows="4" />
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
