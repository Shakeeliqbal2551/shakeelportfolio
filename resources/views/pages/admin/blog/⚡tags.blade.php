<?php

use App\Models\BlogTag;
use App\Support\PortfolioContext;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Admin · Blog Tags')]
class extends Component {
    public ?int $editingId = null;
    public string $name = '';
    public string $slug = '';

    public string $bulkInput = '';

    #[Computed]
    public function items()
    {
        return PortfolioContext::current()
            ?->blogTags()
            ->withCount('posts')
            ->orderBy('name')
            ->get() ?? collect();
    }

    public function rules(): array
    {
        $portfolio = PortfolioContext::current();
        return [
            'name' => ['required', 'string', 'max:60'],
            'slug' => ['required', 'string', 'alpha_dash', 'max:60', "unique:blog_tags,slug,{$this->editingId},id,portfolio_id,{$portfolio?->id}"],
        ];
    }

    public function updatedName(string $value): void
    {
        if ($this->editingId === null) {
            $this->slug = Str::slug($value);
        }
    }

    public function startCreate(): void
    {
        $this->reset(['editingId', 'name', 'slug']);
        \Flux\Flux::modal('tag-editor')->show();
    }

    public function startEdit(int $id): void
    {
        $tag = BlogTag::findOrFail($id);
        $this->editingId = $tag->id;
        $this->name = $tag->name;
        $this->slug = $tag->slug;
        \Flux\Flux::modal('tag-editor')->show();
    }

    public function save(): void
    {
        $data = $this->validate();
        $portfolio = PortfolioContext::current();

        if ($this->editingId) {
            BlogTag::where('id', $this->editingId)->update($data);
        } else {
            BlogTag::create($data + ['portfolio_id' => $portfolio->id]);
        }

        \Flux\Flux::modal('tag-editor')->close();
        \Flux\Flux::toast(heading: __('Saved'), text: __('Tag saved.'), variant: 'success');
        $this->reset(['editingId', 'name', 'slug']);
    }

    public function bulkAdd(): void
    {
        $portfolio = PortfolioContext::current();
        $names = collect(preg_split('/[\r\n,]+/', $this->bulkInput))
            ->map(fn ($n) => trim($n))
            ->filter()
            ->unique();

        $created = 0;
        foreach ($names as $name) {
            $slug = Str::slug($name);
            if (! BlogTag::where('portfolio_id', $portfolio->id)->where('slug', $slug)->exists()) {
                BlogTag::create(['portfolio_id' => $portfolio->id, 'name' => $name, 'slug' => $slug]);
                $created++;
            }
        }

        $this->bulkInput = '';
        \Flux\Flux::toast(heading: __('Added'), text: $created.' '.__('tag(s) created.'), variant: 'success');
    }

    public function delete(int $id): void
    {
        BlogTag::findOrFail($id)->delete();
        \Flux\Flux::toast(heading: __('Deleted'), text: __('Tag removed.'), variant: 'success');
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-2">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Blog Tags') }}</flux:heading>
            <flux:subheading>{{ __('Tag posts for filtering and SEO. A post can have many tags.') }}</flux:subheading>
        </div>
        <div class="flex gap-2">
            <flux:button :href="route('admin.blog.posts')"      wire:navigate icon="document-text">{{ __('Posts') }}</flux:button>
            <flux:button :href="route('admin.blog.categories')" wire:navigate icon="folder">{{ __('Categories') }}</flux:button>
            <flux:button variant="primary" wire:click="startCreate" icon="plus">{{ __('New Tag') }}</flux:button>
        </div>
    </div>

    {{-- Bulk add --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="sm" class="mb-2">{{ __('Bulk add tags') }}</flux:heading>
        <flux:text size="sm" class="mb-3 text-zinc-500">{{ __('Paste tags separated by commas or new lines. Duplicates are skipped.') }}</flux:text>
        <div class="flex gap-2">
            <flux:textarea wire:model="bulkInput" rows="2" class="flex-1" placeholder="Laravel, Livewire, PHP, Vue.js" />
            <flux:button variant="primary" wire:click="bulkAdd" icon="plus">{{ __('Add') }}</flux:button>
        </div>
    </div>

    {{-- Tag chips --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        @if ($this->items->isEmpty())
            <div class="py-8 text-center text-sm text-zinc-500">{{ __('No tags yet.') }}</div>
        @else
            <div class="flex flex-wrap gap-2">
                @foreach ($this->items as $tag)
                    <div class="group inline-flex items-center gap-2 rounded-full border border-zinc-200 bg-zinc-50 py-1 pl-3 pr-1 dark:border-zinc-700 dark:bg-zinc-800" wire:key="tag-{{ $tag->id }}">
                        <button type="button" wire:click="startEdit({{ $tag->id }})" class="text-sm font-medium text-zinc-900 dark:text-zinc-100 hover:text-emerald-500">
                            #{{ $tag->name }}
                        </button>
                        <flux:badge size="sm" color="zinc">{{ $tag->posts_count }}</flux:badge>
                        <button type="button" wire:click="delete({{ $tag->id }})" wire:confirm="{{ __('Remove this tag?') }}" class="rounded-full p-1 text-zinc-400 hover:bg-red-100 hover:text-red-600 dark:hover:bg-red-950">
                            <flux:icon name="x-mark" class="size-3.5" />
                        </button>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <flux:modal name="tag-editor" class="md:w-96">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? __('Edit tag') : __('New tag') }}</flux:heading>
            <flux:input wire:model.live.debounce.500ms="name" label="{{ __('Name') }}" required />
            <flux:input wire:model="slug" label="{{ __('Slug') }}" required />
            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
