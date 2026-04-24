<?php

use App\Models\Project;
use App\Models\ProjectCategory;
use App\Services\MediaService;
use App\Support\PortfolioContext;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('layouts.app')]
#[Title('Admin · Projects')]
class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'cat')]
    public ?int $categoryFilter = null;

    #[Url(as: 'status')]
    public string $statusFilter = '';

    public string $sortBy = 'sort_order';
    public string $sortDir = 'asc';

    #[Computed]
    public function categories()
    {
        return PortfolioContext::current()
            ?->projectCategories()
            ->orderBy('sort_order')
            ->get() ?? collect();
    }

    public function with(): array
    {
        $portfolio = PortfolioContext::current();

        $query = Project::query()
            ->where('portfolio_id', $portfolio?->id)
            ->with(['categories', 'primaryImage', 'images']);

        if ($this->search) {
            $term = '%'.trim($this->search).'%';
            $query->where(fn ($q) => $q->where('title', 'like', $term)
                ->orWhere('tagline', 'like', $term)
                ->orWhere('client', 'like', $term)
                ->orWhere('industry', 'like', $term));
        }

        if ($this->categoryFilter) {
            $catId = $this->categoryFilter;
            $query->whereHas('categories', fn ($q) => $q->where('project_categories.id', $catId));
        }

        if ($this->statusFilter === 'published') $query->where('is_published', true);
        if ($this->statusFilter === 'draft')     $query->where('is_published', false);
        if ($this->statusFilter === 'featured')  $query->where('is_featured', true);
        if ($this->statusFilter === 'saas')      $query->where('is_saas', true);
        if ($this->statusFilter === 'for_sale')  $query->where('is_for_sale', true);

        $query->orderBy($this->sortBy, $this->sortDir);

        return ['projects' => $query->paginate(15)];
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = 'asc';
        }
    }

    public function togglePublished(int $id): void
    {
        $project = Project::findOrFail($id);
        $project->update(['is_published' => ! $project->is_published]);
    }

    public function toggleFeatured(int $id): void
    {
        $project = Project::findOrFail($id);
        $project->update(['is_featured' => ! $project->is_featured]);
    }

    public function delete(int $id, MediaService $media): void
    {
        $project = Project::with('images')->findOrFail($id);

        // soft-delete keeps files; only hard-delete removes them
        $project->delete();

        \Flux\Flux::toast(heading: __('Deleted'), text: __('Project moved to trash. Files are kept.'), variant: 'success');
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-2">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Projects') }}</flux:heading>
            <flux:subheading>{{ __('Featured work shown in the public grid. Drag-and-drop reorder coming later — for now use the sort_order field.') }}</flux:subheading>
        </div>
        <div class="flex gap-2">
            <flux:button :href="route('admin.projects.categories')" wire:navigate icon="tag">{{ __('Categories') }}</flux:button>
            <flux:button variant="primary" :href="route('admin.projects.create')" wire:navigate icon="plus">{{ __('New Project') }}</flux:button>
        </div>
    </div>

    {{-- Filter bar --}}
    <div class="flex flex-wrap items-center gap-3 rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex-1 min-w-[220px]">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search title, client, industry…') }}" icon="magnifying-glass" />
        </div>
        <flux:select wire:model.live="categoryFilter" placeholder="{{ __('All categories') }}" class="min-w-[180px]">
            <option value="">{{ __('All categories') }}</option>
            @foreach ($this->categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="statusFilter" class="min-w-[160px]">
            <option value="">{{ __('Any status') }}</option>
            <option value="published">{{ __('Published') }}</option>
            <option value="draft">{{ __('Draft') }}</option>
            <option value="featured">{{ __('Featured') }}</option>
            <option value="saas">{{ __('SaaS') }}</option>
            <option value="for_sale">{{ __('For sale') }}</option>
        </flux:select>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <table class="w-full text-sm">
            <thead class="border-b border-zinc-200 bg-zinc-50 text-left dark:border-zinc-700 dark:bg-zinc-800">
                <tr>
                    <th class="px-4 py-3 font-semibold">{{ __('Project') }}</th>
                    <th class="px-4 py-3 font-semibold">{{ __('Categories') }}</th>
                    <th class="px-4 py-3 font-semibold">{{ __('Flags') }}</th>
                    <th class="px-4 py-3 font-semibold cursor-pointer" wire:click="sort('sort_order')">
                        {{ __('Sort') }}
                        @if ($sortBy === 'sort_order') <flux:icon :name="$sortDir === 'asc' ? 'chevron-up' : 'chevron-down'" class="inline size-3" /> @endif
                    </th>
                    <th class="px-4 py-3 text-right font-semibold">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($projects as $project)
                    <tr class="border-b border-zinc-100 last:border-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/50" wire:key="project-{{ $project->id }}">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                @if ($url = $project->primaryImageUrl())
                                    <img src="{{ $url }}" alt="" class="size-12 rounded-md object-cover" />
                                @else
                                    <div class="flex size-12 items-center justify-center rounded-md bg-zinc-200 text-zinc-400 dark:bg-zinc-800">
                                        <flux:icon name="photo" class="size-5" />
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <a href="{{ route('admin.projects.edit', $project->id) }}" wire:navigate class="font-semibold text-zinc-900 hover:text-emerald-500 dark:text-zinc-100">{{ $project->title }}</a>
                                    @if ($project->tagline)
                                        <div class="truncate text-xs text-zinc-500">{{ $project->tagline }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                @foreach ($project->categories as $cat)
                                    <flux:badge size="sm" color="zinc">{{ $cat->name }}</flux:badge>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                <button wire:click="togglePublished({{ $project->id }})" class="cursor-pointer">
                                    <flux:badge size="sm" :color="$project->is_published ? 'emerald' : 'zinc'">{{ $project->is_published ? __('Published') : __('Draft') }}</flux:badge>
                                </button>
                                <button wire:click="toggleFeatured({{ $project->id }})" class="cursor-pointer">
                                    <flux:badge size="sm" :color="$project->is_featured ? 'amber' : 'zinc'">★ {{ __('Featured') }}</flux:badge>
                                </button>
                                @if ($project->is_saas)
                                    <flux:badge size="sm" color="blue">{{ __('SaaS') }}</flux:badge>
                                @endif
                                @if ($project->is_for_sale)
                                    <flux:badge size="sm" color="purple">{{ __('For Sale') }}</flux:badge>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3 text-zinc-500">{{ $project->sort_order }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-1">
                                @if ($project->live_url)
                                    <flux:button size="sm" variant="ghost" icon="arrow-top-right-on-square" :href="$project->live_url" target="_blank" />
                                @endif
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('admin.projects.edit', $project->id)" wire:navigate />
                                <flux:modal.trigger :name="'delete-project-'.$project->id">
                                    <flux:button size="sm" variant="danger" icon="trash" />
                                </flux:modal.trigger>
                            </div>

                            <flux:modal :name="'delete-project-'.$project->id" class="md:w-96">
                                <div class="space-y-4">
                                    <flux:heading size="lg">{{ __('Delete project?') }}</flux:heading>
                                    <flux:text>{{ __('Soft-delete moves the project to trash. Images are kept on disk and the project can be restored from the database.') }}</flux:text>
                                    <div class="flex justify-end gap-2">
                                        <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                                        <flux:button variant="danger" wire:click="delete({{ $project->id }})">{{ __('Delete') }}</flux:button>
                                    </div>
                                </div>
                            </flux:modal>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-12 text-center text-zinc-500">{{ __('No projects match your filters.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $projects->links() }}</div>
</div>
