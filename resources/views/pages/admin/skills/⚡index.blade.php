<?php

use App\Models\Skill;
use App\Support\PortfolioContext;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Admin · Skills')]
class extends Component {
    public ?int $editingId = null;
    public ?int $skill_category_id = null;
    public string $name = '';
    public ?int $proficiency = null;
    public ?int $years_experience = null;
    public string $icon = '';
    public bool $is_featured = false;
    public bool $is_active = true;
    public int $sort_order = 0;

    #[Computed]
    public function skillsByCategory()
    {
        $portfolio = PortfolioContext::current();

        $categories = $portfolio?->skillCategories()->orderBy('sort_order')->get() ?? collect();
        $uncategorised = Skill::where('portfolio_id', $portfolio?->id)
            ->whereNull('skill_category_id')
            ->orderBy('sort_order')
            ->get();

        return [
            'categories'    => $categories->loadMissing('skills'),
            'uncategorised' => $uncategorised,
        ];
    }

    #[Computed]
    public function categoriesList()
    {
        return PortfolioContext::current()?->skillCategories()->orderBy('sort_order')->get() ?? collect();
    }

    public function rules(): array
    {
        return [
            'skill_category_id' => ['nullable', 'integer', 'exists:skill_categories,id'],
            'name'              => ['required', 'string', 'max:120'],
            'proficiency'       => ['nullable', 'integer', 'min:0', 'max:100'],
            'years_experience'  => ['nullable', 'integer', 'min:0', 'max:99'],
            'icon'              => ['nullable', 'string', 'max:60'],
            'is_featured'       => ['boolean'],
            'is_active'         => ['boolean'],
            'sort_order'        => ['integer', 'min:0', 'max:9999'],
        ];
    }

    public function startCreate(?int $categoryId = null): void
    {
        $this->reset(['editingId', 'name', 'proficiency', 'years_experience', 'icon']);
        $this->skill_category_id = $categoryId;
        $this->is_featured = false;
        $this->is_active = true;
        $portfolio = PortfolioContext::current();
        $this->sort_order = (int) (($portfolio?->skills()
            ->when($categoryId, fn ($q) => $q->where('skill_category_id', $categoryId))
            ->max('sort_order') ?? 0) + 10);
        \Flux\Flux::modal('skill-editor')->show();
    }

    public function startEdit(int $id): void
    {
        $skill = Skill::findOrFail($id);
        $this->editingId         = $skill->id;
        $this->skill_category_id = $skill->skill_category_id;
        $this->name              = $skill->name;
        $this->proficiency       = $skill->proficiency;
        $this->years_experience  = $skill->years_experience;
        $this->icon              = $skill->icon ?? '';
        $this->is_featured       = (bool) $skill->is_featured;
        $this->is_active         = (bool) $skill->is_active;
        $this->sort_order        = $skill->sort_order;
        \Flux\Flux::modal('skill-editor')->show();
    }

    public function save(): void
    {
        $data = $this->validate();
        $portfolio = PortfolioContext::current();

        if ($this->editingId) {
            Skill::where('id', $this->editingId)->update($data);
        } else {
            Skill::create($data + ['portfolio_id' => $portfolio->id]);
        }

        \Flux\Flux::modal('skill-editor')->close();
        \Flux\Flux::toast(heading: __('Saved'), text: __('Skill saved.'), variant: 'success');
        $this->reset(['editingId', 'name']);
    }

    public function toggleActive(int $id): void
    {
        $s = Skill::findOrFail($id);
        $s->update(['is_active' => ! $s->is_active]);
    }

    public function toggleFeatured(int $id): void
    {
        $s = Skill::findOrFail($id);
        $s->update(['is_featured' => ! $s->is_featured]);
    }

    public function delete(int $id): void
    {
        Skill::findOrFail($id)->delete();
        \Flux\Flux::toast(heading: __('Deleted'), text: __('Skill removed.'), variant: 'success');
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-2">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Skills') }}</flux:heading>
            <flux:subheading>{{ __('Manage your stack. Skills appear under their category in the About → Skills tab.') }}</flux:subheading>
        </div>
        <div class="flex gap-2">
            <flux:button :href="route('admin.skills.categories')" wire:navigate icon="rectangle-stack">{{ __('Categories') }}</flux:button>
            <flux:button variant="primary" wire:click="startCreate" icon="plus">{{ __('New Skill') }}</flux:button>
        </div>
    </div>

    @php
        $data = $this->skillsByCategory;
        $categories = $data['categories'];
        $uncategorised = $data['uncategorised'];
    @endphp

    @forelse ($categories as $cat)
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900" wire:key="cat-{{ $cat->id }}">
            <div class="flex items-center justify-between border-b border-zinc-100 px-5 py-3 dark:border-zinc-800">
                <div class="flex items-center gap-2">
                    @if ($cat->icon) <flux:icon :name="$cat->icon" class="size-4 text-zinc-500" /> @endif
                    <flux:heading size="sm">{{ $cat->name }}</flux:heading>
                    <flux:badge size="sm" color="zinc">{{ $cat->skills->count() }}</flux:badge>
                </div>
                <flux:button size="sm" wire:click="startCreate({{ $cat->id }})" icon="plus">{{ __('Add to') }} {{ $cat->name }}</flux:button>
            </div>
            @if ($cat->skills->isEmpty())
                <div class="px-5 py-8 text-center text-sm text-zinc-500">{{ __('No skills yet.') }}</div>
            @else
                <div class="grid gap-2 p-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($cat->skills->sortBy('sort_order') as $skill)
                        <div class="flex items-center justify-between gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800/50" wire:key="sk-{{ $skill->id }}">
                            <div class="flex min-w-0 items-center gap-2">
                                <span class="size-2 rounded-full {{ $skill->is_active ? 'bg-emerald-400' : 'bg-zinc-500' }}"></span>
                                <span class="truncate font-medium text-zinc-900 dark:text-zinc-100">{{ $skill->name }}</span>
                                @if ($skill->is_featured) <flux:icon name="star" class="size-3 text-amber-500" /> @endif
                                @if ($skill->proficiency)<flux:badge size="sm" color="zinc">{{ $skill->proficiency }}%</flux:badge>@endif
                            </div>
                            <div class="flex shrink-0 gap-1">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="startEdit({{ $skill->id }})" />
                                <flux:button size="sm" variant="danger" icon="trash" wire:click="delete({{ $skill->id }})" wire:confirm="{{ __('Remove this skill?') }}" />
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @empty
        <flux:callout icon="information-circle">
            {{ __('No skill categories yet.') }} <flux:link :href="route('admin.skills.categories')" wire:navigate>{{ __('Create one first') }} →</flux:link>
        </flux:callout>
    @endforelse

    @if ($uncategorised->isNotEmpty())
        <div class="rounded-xl border border-amber-200 bg-amber-50/50 dark:border-amber-900 dark:bg-amber-950/20">
            <div class="border-b border-amber-200 px-5 py-3 dark:border-amber-900">
                <flux:heading size="sm">{{ __('Uncategorised') }} <flux:badge size="sm" color="amber">{{ $uncategorised->count() }}</flux:badge></flux:heading>
            </div>
            <div class="grid gap-2 p-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($uncategorised as $skill)
                    <div class="flex items-center justify-between gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900" wire:key="u-{{ $skill->id }}">
                        <span class="truncate text-zinc-900 dark:text-zinc-100">{{ $skill->name }}</span>
                        <div class="flex shrink-0 gap-1">
                            <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="startEdit({{ $skill->id }})" />
                            <flux:button size="sm" variant="danger" icon="trash" wire:click="delete({{ $skill->id }})" wire:confirm="{{ __('Remove this skill?') }}" />
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Editor modal --}}
    <flux:modal name="skill-editor" class="md:w-[520px]">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingId ? __('Edit skill') : __('New skill') }}</flux:heading>
            </div>
            <flux:input wire:model="name" label="{{ __('Skill name') }}" placeholder="Laravel" required />
            <flux:select wire:model="skill_category_id" label="{{ __('Category') }}">
                <option value="">{{ __('— Uncategorised —') }}</option>
                @foreach ($this->categoriesList as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </flux:select>
            <div class="grid grid-cols-3 gap-3">
                <flux:input type="number" wire:model="proficiency" label="{{ __('Proficiency %') }}" min="0" max="100" />
                <flux:input type="number" wire:model="years_experience" label="{{ __('Years exp.') }}" min="0" max="99" />
                <flux:input type="number" wire:model="sort_order" label="{{ __('Sort order') }}" min="0" />
            </div>
            <flux:input wire:model="icon" label="{{ __('Icon (Heroicon)') }}" placeholder="cube" />
            <div class="flex flex-col gap-2">
                <flux:switch wire:model="is_active"   label="{{ __('Active') }}" />
                <flux:switch wire:model="is_featured" label="{{ __('Featured') }}" />
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
