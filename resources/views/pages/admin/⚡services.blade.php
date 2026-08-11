<?php

use App\Models\Service;
use App\Support\PortfolioContext;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Admin · Services')]
class extends Component {
    public ?int $editingId = null;
    public string $title = '';
    public string $slug = '';
    public string $icon = '';
    public string $summary = '';
    public string $description = '';
    public ?float $starting_price = null;
    public string $currency = 'USD';
    public string $billing_cycle = '';
    public array $included_features = [];
    public bool $is_featured = false;
    public bool $is_active = true;
    public int $sort_order = 0;

    #[Computed]
    public function items()
    {
        return PortfolioContext::current()
            ?->services()
            ->orderBy('sort_order')
            ->get() ?? collect();
    }

    public function rules(): array
    {
        $portfolio = PortfolioContext::current();
        $unique = "unique:services,slug,{$this->editingId},id,portfolio_id,{$portfolio?->id}";

        return [
            'title'             => ['required', 'string', 'max:160'],
            'slug'              => ['required', 'string', 'alpha_dash', 'max:160', $unique],
            'icon'              => ['nullable', 'string', 'max:60'],
            'summary'           => ['nullable', 'string', 'max:500'],
            'description'       => ['nullable', 'string'],
            'starting_price'    => ['nullable', 'numeric', 'min:0'],
            'currency'          => ['nullable', 'string', 'max:8'],
            'billing_cycle'     => ['nullable', 'string', 'max:32'],
            'included_features' => ['array'],
            'included_features.*' => ['nullable', 'string', 'max:200'],
            'is_featured'       => ['boolean'],
            'is_active'         => ['boolean'],
            'sort_order'        => ['integer', 'min:0', 'max:9999'],
        ];
    }

    public function updatedTitle(string $value): void
    {
        if ($this->editingId === null && empty($this->slug)) {
            $this->slug = Str::slug($value);
        }
    }

    public function addFeature(): void { $this->included_features[] = ''; }
    public function removeFeature(int $i): void { unset($this->included_features[$i]); $this->included_features = array_values($this->included_features); }

    public function startCreate(): void
    {
        $this->reset(['editingId', 'title', 'slug', 'icon', 'summary', 'description', 'starting_price', 'billing_cycle', 'included_features']);
        $this->currency = 'USD';
        $this->is_featured = false;
        $this->is_active = true;
        $this->sort_order = (int) ((PortfolioContext::current()?->services()->max('sort_order') ?? 0) + 10);
        \Flux\Flux::modal('service-editor')->show();
    }

    public function startEdit(int $id): void
    {
        $s = Service::findOrFail($id);
        $this->editingId         = $s->id;
        $this->title             = $s->title;
        $this->slug              = $s->slug;
        $this->icon              = $s->icon ?? '';
        $this->summary           = $s->summary ?? '';
        $this->description       = $s->description ?? '';
        $this->starting_price    = $s->starting_price !== null ? (float) $s->starting_price : null;
        $this->currency          = $s->currency ?? 'USD';
        $this->billing_cycle     = $s->billing_cycle ?? '';
        $this->included_features = $s->included_features ?? [];
        $this->is_featured       = (bool) $s->is_featured;
        $this->is_active         = (bool) $s->is_active;
        $this->sort_order        = $s->sort_order;
        \Flux\Flux::modal('service-editor')->show();
    }

    public function save(): void
    {
        $data = $this->validate();
        $data['included_features'] = array_values(array_filter($data['included_features'], fn ($v) => trim((string) $v) !== ''));
        $portfolio = PortfolioContext::current();

        if ($this->editingId) {
            Service::where('id', $this->editingId)->update($data);
        } else {
            Service::create($data + ['portfolio_id' => $portfolio->id]);
        }

        \Flux\Flux::modal('service-editor')->close();
        \Flux\Flux::toast(heading: __('Saved'), text: __('Service saved.'), variant: 'success');
        $this->reset(['editingId']);
    }

    public function toggleActive(int $id): void
    {
        $s = Service::findOrFail($id);
        $s->update(['is_active' => ! $s->is_active]);
    }

    public function delete(int $id): void
    {
        Service::findOrFail($id)->delete();
        \Flux\Flux::toast(heading: __('Deleted'), text: __('Service removed.'), variant: 'success');
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-2">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Services') }}</flux:heading>
            <flux:subheading>{{ __('What you offer to clients. Shown in the Services section of your portfolio.') }}</flux:subheading>
        </div>
        <flux:button variant="primary" wire:click="startCreate" icon="plus">{{ __('New Service') }}</flux:button>
    </div>

    <div class="grid gap-3 md:grid-cols-2">
        @forelse ($this->items as $s)
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900" wire:key="svc-{{ $s->id }}">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            @if ($s->icon) <flux:icon :name="$s->icon" class="size-4 text-emerald-500" /> @endif
                            <flux:heading size="md">{{ $s->title }}</flux:heading>
                            @if ($s->is_featured) <flux:icon name="star" class="size-3 text-amber-500" /> @endif
                            @if (! $s->is_active) <flux:badge size="sm" color="zinc">{{ __('Hidden') }}</flux:badge> @endif
                        </div>
                        @if ($s->summary)
                            <flux:text size="sm" class="mt-2 text-zinc-600 dark:text-zinc-400">{{ $s->summary }}</flux:text>
                        @endif
                        @if ($s->starting_price)
                            <flux:badge size="sm" color="emerald" class="mt-3">
                                {{ __('From') }} {{ $s->currency }} {{ number_format($s->starting_price, 0) }}
                                @if ($s->billing_cycle) / {{ $s->billing_cycle }} @endif
                            </flux:badge>
                        @endif
                    </div>
                    <div class="flex shrink-0 gap-1">
                        <flux:button size="sm" variant="ghost" :icon="$s->is_active ? 'eye-slash' : 'eye'" wire:click="toggleActive({{ $s->id }})" />
                        <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="startEdit({{ $s->id }})" />
                        <flux:button size="sm" variant="danger" icon="trash" wire:click="delete({{ $s->id }})" wire:confirm="{{ __('Delete this service?') }}" />
                    </div>
                </div>
            </div>
        @empty
            <div class="md:col-span-2 rounded-xl border border-dashed border-zinc-300 p-12 text-center text-zinc-500 dark:border-zinc-700">
                {{ __('No services yet.') }}
            </div>
        @endforelse
    </div>

    <flux:modal name="service-editor" class="md:w-[640px]">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? __('Edit service') : __('New service') }}</flux:heading>
            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model.live.debounce.500ms="title" label="{{ __('Title') }}" required />
                <flux:input wire:model="slug" label="{{ __('Slug') }}" required />
                <flux:input wire:model="icon" label="{{ __('Icon (Heroicon)') }}" placeholder="briefcase" />
                <flux:input type="number" wire:model="sort_order" label="{{ __('Sort order') }}" min="0" />
            </div>
            <flux:textarea wire:model="summary" label="{{ __('Short summary') }}" rows="2" />
            <flux:textarea wire:model="description" label="{{ __('Long description (markdown ok)') }}" rows="5" />

            <div class="grid gap-4 md:grid-cols-3">
                <flux:input type="number" step="0.01" wire:model="starting_price" label="{{ __('Starting price') }}" placeholder="500" />
                <flux:select wire:model="currency" label="{{ __('Currency') }}">
                    <option value="USD">USD</option><option value="EUR">EUR</option>
                    <option value="GBP">GBP</option><option value="PKR">PKR</option><option value="AED">AED</option>
                </flux:select>
                <flux:select wire:model="billing_cycle" label="{{ __('Billing') }}">
                    <option value="">—</option>
                    <option value="hour">{{ __('per hour') }}</option>
                    <option value="project">{{ __('per project') }}</option>
                    <option value="month">{{ __('per month') }}</option>
                </flux:select>
            </div>

            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="mb-3 flex items-center justify-between">
                    <flux:heading size="sm">{{ __('Included features') }}</flux:heading>
                    <flux:button size="sm" wire:click="addFeature" icon="plus" type="button">{{ __('Add') }}</flux:button>
                </div>
                <div class="space-y-2">
                    @foreach ($included_features as $i => $f)
                        <div class="flex items-center gap-2" wire:key="feat-{{ $i }}">
                            <flux:input wire:model="included_features.{{ $i }}" class="flex-1" />
                            <flux:button size="sm" variant="danger" wire:click="removeFeature({{ $i }})" icon="trash" type="button" />
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <flux:switch wire:model="is_active"   label="{{ __('Active (shown on site)') }}" />
                <flux:switch wire:model="is_featured" label="{{ __('Featured') }}" />
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
