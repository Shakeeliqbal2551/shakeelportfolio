<?php

use App\Models\ProjectCategory;
use App\Support\PortfolioContext;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Admin · Project Categories')]
class extends Component {
    public ?int $editingId = null;
    public string $name = '';
    public string $slug = '';
    public string $color = '#5eead4';
    public int $sort_order = 0;
    public bool $is_active = true;

    #[Computed]
    public function categories()
    {
        return PortfolioContext::current()
            ?->projectCategories()
            ->withCount('projects')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get() ?? collect();
    }

    public function rules(): array
    {
        $portfolio = PortfolioContext::current();
        $unique = "unique:project_categories,slug,{$this->editingId},id,portfolio_id,{$portfolio?->id}";

        return [
            'name'       => ['required', 'string', 'max:80'],
            'slug'       => ['required', 'string', 'alpha_dash', 'max:80', $unique],
            'color'      => ['nullable', 'string', 'max:16'],
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
        $this->reset(['editingId', 'name', 'slug', 'is_active']);
        $this->color = '#5eead4';
        $this->sort_order = (int) ((PortfolioContext::current()?->projectCategories()->max('sort_order') ?? 0) + 10);
        $this->is_active = true;
        \Flux\Flux::modal('category-editor')->show();
    }

    public function startEdit(int $id): void
    {
        $cat = ProjectCategory::findOrFail($id);

        $this->editingId  = $cat->id;
        $this->name       = $cat->name;
        $this->slug       = $cat->slug;
        $this->color      = $cat->color ?? '#5eead4';
        $this->sort_order = $cat->sort_order;
        $this->is_active  = (bool) $cat->is_active;

        \Flux\Flux::modal('category-editor')->show();
    }

    public function save(): void
    {
        $data = $this->validate();
        $portfolio = PortfolioContext::current();

        if ($this->editingId) {
            ProjectCategory::where('id', $this->editingId)->update($data);
        } else {
            ProjectCategory::create($data + ['portfolio_id' => $portfolio->id]);
        }

        \Flux\Flux::modal('category-editor')->close();
        \Flux\Flux::toast(heading: __('Saved'), text: __('Category saved.'), variant: 'success');
        $this->reset(['editingId', 'name', 'slug']);
    }

    public function toggleActive(int $id): void
    {
        $cat = ProjectCategory::findOrFail($id);
        $cat->update(['is_active' => ! $cat->is_active]);
    }

    public function delete(int $id): void
    {
        $cat = ProjectCategory::withCount('projects')->findOrFail($id);

        if ($cat->projects_count > 0) {
            \Flux\Flux::toast(heading: __('Cannot delete'), text: __('Detach the category from all projects first.'), variant: 'danger');
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
            <flux:heading size="xl">{{ __('Project Categories') }}</flux:heading>
            <flux:subheading>{{ __('Filter tabs shown above the projects grid. Each project can belong to multiple categories.') }}</flux:subheading>
        </div>
        <flux:button variant="primary" wire:click="startCreate" icon="plus">{{ __('New Category') }}</flux:button>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <table class="w-full text-sm">
            <thead class="border-b border-zinc-200 bg-zinc-50 text-left dark:border-zinc-700 dark:bg-zinc-800">
                <tr>
                    <th class="px-4 py-3 font-semibold">{{ __('Name') }}</th>
                    <th class="px-4 py-3 font-semibold">{{ __('Slug') }}</th>
                    <th class="px-4 py-3 font-semibold">{{ __('Projects') }}</th>
                    <th class="px-4 py-3 font-semibold">{{ __('Sort') }}</th>
                    <th class="px-4 py-3 font-semibold">{{ __('Status') }}</th>
                    <th class="px-4 py-3 text-right font-semibold">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->categories as $cat)
                    <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800" wire:key="cat-{{ $cat->id }}">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span class="size-3 rounded-full" style="background:{{ $cat->color ?? '#5eead4' }}"></span>
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $cat->name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-zinc-500">{{ $cat->slug }}</td>
                        <td class="px-4 py-3">
                            <flux:badge size="sm" color="zinc">{{ $cat->projects_count }}</flux:badge>
                        </td>
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
                                <flux:modal.trigger :name="'delete-cat-'.$cat->id">
                                    <flux:button size="sm" variant="danger" icon="trash" />
                                </flux:modal.trigger>
                            </div>

                            <flux:modal :name="'delete-cat-'.$cat->id" class="md:w-96">
                                <div class="space-y-4">
                                    <flux:heading size="lg">{{ __('Delete category?') }}</flux:heading>
                                    <flux:text>{{ __('This permanently removes the category. Projects in it are NOT deleted — they just lose this label.') }}</flux:text>
                                    <div class="flex justify-end gap-2">
                                        <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                                        <flux:button variant="danger" wire:click="delete({{ $cat->id }})">{{ __('Delete') }}</flux:button>
                                    </div>
                                </div>
                            </flux:modal>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-12 text-center text-zinc-500">{{ __('No categories yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Editor modal --}}
    <flux:modal name="category-editor" class="md:w-[480px]">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingId ? __('Edit category') : __('New category') }}</flux:heading>
                <flux:text size="sm" class="text-zinc-500">{{ __('Used as a filter tab on the public Featured Projects section.') }}</flux:text>
            </div>

            <flux:input wire:model.live.debounce.500ms="name" label="{{ __('Name') }}" placeholder="SaaS" required />
            <flux:input wire:model="slug" label="{{ __('Slug') }}" description="{{ __('Used in URL filters and the data-filter attribute.') }}" required />

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <flux:label>{{ __('Accent color') }}</flux:label>
                    <div class="mt-1 flex gap-2">
                        <input type="color" wire:model="color" class="h-10 w-12 cursor-pointer rounded border border-zinc-300 bg-white p-1 dark:border-zinc-600 dark:bg-zinc-800" />
                        <flux:input wire:model="color" class="flex-1 font-mono" />
                    </div>
                </div>
                <flux:input type="number" wire:model="sort_order" label="{{ __('Sort order') }}" min="0" />
            </div>

            <flux:switch wire:model="is_active" label="{{ __('Active (visible as filter tab)') }}" />

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
