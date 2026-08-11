<?php

use App\Models\SkillCategory;
use App\Support\PortfolioContext;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Admin · Skill Categories')]
class extends Component {
    public ?int $editingId = null;
    public string $name = '';
    public string $slug = '';
    public string $icon = '';
    public int $sort_order = 0;
    public bool $is_active = true;

    #[Computed]
    public function categories()
    {
        return PortfolioContext::current()
            ?->skillCategories()
            ->withCount('skills')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get() ?? collect();
    }

    public function rules(): array
    {
        $portfolio = PortfolioContext::current();
        $unique = "unique:skill_categories,slug,{$this->editingId},id,portfolio_id,{$portfolio?->id}";

        return [
            'name'       => ['required', 'string', 'max:80'],
            'slug'       => ['required', 'string', 'alpha_dash', 'max:80', $unique],
            'icon'       => ['nullable', 'string', 'max:60'],
            'sort_order' => ['integer', 'min:0', 'max:9999'],
            'is_active'  => ['boolean'],
        ];
    }

    public function updatedName(string $value): void
    {
        if ($this->editingId === null && empty($this->slug)) {
            $this->slug = Str::slug($value);
        }
    }

    public function startCreate(): void
    {
        $this->reset(['editingId', 'name', 'slug', 'icon']);
        $this->sort_order = (int) ((PortfolioContext::current()?->skillCategories()->max('sort_order') ?? 0) + 10);
        $this->is_active = true;
        \Flux\Flux::modal('skillcat-editor')->show();
    }

    public function startEdit(int $id): void
    {
        $cat = SkillCategory::findOrFail($id);
        $this->editingId  = $cat->id;
        $this->name       = $cat->name;
        $this->slug       = $cat->slug;
        $this->icon       = $cat->icon ?? '';
        $this->sort_order = $cat->sort_order;
        $this->is_active  = (bool) $cat->is_active;
        \Flux\Flux::modal('skillcat-editor')->show();
    }

    public function save(): void
    {
        $data = $this->validate();
        $portfolio = PortfolioContext::current();

        if ($this->editingId) {
            SkillCategory::where('id', $this->editingId)->update($data);
        } else {
            SkillCategory::create($data + ['portfolio_id' => $portfolio->id]);
        }

        \Flux\Flux::modal('skillcat-editor')->close();
        \Flux\Flux::toast(heading: __('Saved'), text: __('Skill category saved.'), variant: 'success');
        $this->reset(['editingId', 'name', 'slug', 'icon']);
    }

    public function toggleActive(int $id): void
    {
        $cat = SkillCategory::findOrFail($id);
        $cat->update(['is_active' => ! $cat->is_active]);
    }

    public function delete(int $id): void
    {
        $cat = SkillCategory::withCount('skills')->findOrFail($id);

        if ($cat->skills_count > 0) {
            \Flux\Flux::toast(heading: __('Cannot delete'), text: __('Move or delete the :n skills first.', ['n' => $cat->skills_count]), variant: 'danger');
            return;
        }

        $cat->delete();
        \Flux\Flux::toast(heading: __('Deleted'), text: __('Category removed.'), variant: 'success');
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-2">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Skill Categories') }}</flux:heading>
            <flux:subheading>{{ __('Group your skills (Backend, Frontend, DevOps, …). Each skill belongs to one category.') }}</flux:subheading>
        </div>
        <div class="flex gap-2">
            <flux:button :href="route('admin.skills.index')" wire:navigate icon="academic-cap">{{ __('Skills') }}</flux:button>
            <flux:button variant="primary" wire:click="startCreate" icon="plus">{{ __('New Category') }}</flux:button>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <table class="w-full text-sm">
            <thead class="border-b border-zinc-200 bg-zinc-50 text-left dark:border-zinc-700 dark:bg-zinc-800">
                <tr>
                    <th class="px-4 py-3 font-semibold">{{ __('Name') }}</th>
                    <th class="px-4 py-3 font-semibold">{{ __('Slug') }}</th>
                    <th class="px-4 py-3 font-semibold">{{ __('Skills') }}</th>
                    <th class="px-4 py-3 font-semibold">{{ __('Sort') }}</th>
                    <th class="px-4 py-3 font-semibold">{{ __('Status') }}</th>
                    <th class="px-4 py-3 text-right font-semibold">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->categories as $cat)
                    <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800" wire:key="skillcat-{{ $cat->id }}">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                @if ($cat->icon) <flux:icon :name="$cat->icon" class="size-4 text-zinc-500" /> @endif
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $cat->name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-zinc-500">{{ $cat->slug }}</td>
                        <td class="px-4 py-3"><flux:badge size="sm" color="zinc">{{ $cat->skills_count }}</flux:badge></td>
                        <td class="px-4 py-3 text-zinc-500">{{ $cat->sort_order }}</td>
                        <td class="px-4 py-3">
                            <button wire:click="toggleActive({{ $cat->id }})" class="cursor-pointer">
                                <flux:badge :color="$cat->is_active ? 'emerald' : 'zinc'" size="sm">
                                    {{ $cat->is_active ? __('Active') : __('Hidden') }}
                                </flux:badge>
                            </button>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-1">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="startEdit({{ $cat->id }})" />
                                <flux:button size="sm" variant="danger" icon="trash" wire:click="delete({{ $cat->id }})" wire:confirm="{{ __('Delete this category?') }}" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-12 text-center text-zinc-500">{{ __('No skill categories yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <flux:modal name="skillcat-editor" class="md:w-[480px]">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingId ? __('Edit category') : __('New skill category') }}</flux:heading>
            </div>
            <flux:input wire:model.live.debounce.500ms="name" label="{{ __('Name') }}" placeholder="Backend" required />
            <flux:input wire:model="slug" label="{{ __('Slug') }}" required />
            <flux:input wire:model="icon" label="{{ __('Icon (Heroicon name)') }}" placeholder="server" description="{{ __('Optional. Examples: server, layout, database, box.') }}" />
            <flux:input type="number" wire:model="sort_order" label="{{ __('Sort order') }}" min="0" />
            <flux:switch wire:model="is_active" label="{{ __('Active') }}" />
            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
