<?php

use App\Models\Post;
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
#[Title('Admin · Blog Posts')]
class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'cat')]
    public ?int $categoryFilter = null;

    #[Url(as: 'status')]
    public string $statusFilter = '';

    #[Computed]
    public function categories()
    {
        return PortfolioContext::current()
            ?->blogCategories()
            ->orderBy('sort_order')
            ->get() ?? collect();
    }

    public function with(): array
    {
        $portfolio = PortfolioContext::current();

        $query = Post::query()
            ->where('portfolio_id', $portfolio?->id)
            ->with(['category', 'tags', 'author']);

        if ($this->search) {
            $term = '%'.trim($this->search).'%';
            $query->where(fn ($q) => $q->where('title', 'like', $term)
                ->orWhere('excerpt', 'like', $term)
                ->orWhere('content', 'like', $term));
        }

        if ($this->categoryFilter) {
            $query->where('blog_category_id', $this->categoryFilter);
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        $query->latest('published_at')->latest('id');

        return ['posts' => $query->paginate(15)];
    }

    public function togglePublish(int $id): void
    {
        $post = Post::findOrFail($id);
        if ($post->status === Post::STATUS_PUBLISHED) {
            $post->update(['status' => Post::STATUS_DRAFT]);
        } else {
            $post->update([
                'status'       => Post::STATUS_PUBLISHED,
                'published_at' => $post->published_at ?? now(),
            ]);
        }
    }

    public function toggleFeatured(int $id): void
    {
        $post = Post::findOrFail($id);
        $post->update(['is_featured' => ! $post->is_featured]);
    }

    public function delete(int $id, MediaService $media): void
    {
        Post::findOrFail($id)->delete();
        \Flux\Flux::toast(heading: __('Deleted'), text: __('Post moved to trash.'), variant: 'success');
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-2">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Blog Posts') }}</flux:heading>
            <flux:subheading>{{ __('Write articles to build authority and SEO. Drafts are private; published posts appear on your site.') }}</flux:subheading>
        </div>
        <div class="flex gap-2">
            <flux:button :href="route('admin.blog.categories')" wire:navigate icon="folder">{{ __('Categories') }}</flux:button>
            <flux:button :href="route('admin.blog.tags')"       wire:navigate icon="hashtag">{{ __('Tags') }}</flux:button>
            <flux:button variant="primary" :href="route('admin.blog.posts.create')" wire:navigate icon="pencil-square">{{ __('Write Post') }}</flux:button>
        </div>
    </div>

    {{-- Filter bar --}}
    <div class="flex flex-wrap items-center gap-3 rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex-1 min-w-[220px]">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search title, excerpt, content…') }}" icon="magnifying-glass" />
        </div>
        <flux:select wire:model.live="categoryFilter" class="min-w-[180px]">
            <option value="">{{ __('All categories') }}</option>
            @foreach ($this->categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="statusFilter" class="min-w-[160px]">
            <option value="">{{ __('Any status') }}</option>
            <option value="draft">{{ __('Draft') }}</option>
            <option value="published">{{ __('Published') }}</option>
            <option value="scheduled">{{ __('Scheduled') }}</option>
            <option value="archived">{{ __('Archived') }}</option>
        </flux:select>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <table class="w-full text-sm">
            <thead class="border-b border-zinc-200 bg-zinc-50 text-left dark:border-zinc-700 dark:bg-zinc-800">
                <tr>
                    <th class="px-4 py-3 font-semibold">{{ __('Post') }}</th>
                    <th class="px-4 py-3 font-semibold">{{ __('Category') }}</th>
                    <th class="px-4 py-3 font-semibold">{{ __('Status') }}</th>
                    <th class="px-4 py-3 font-semibold">{{ __('Published') }}</th>
                    <th class="px-4 py-3 font-semibold">{{ __('Views') }}</th>
                    <th class="px-4 py-3 text-right font-semibold">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($posts as $post)
                    <tr class="border-b border-zinc-100 last:border-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/50" wire:key="post-{{ $post->id }}">
                        <td class="px-4 py-3">
                            <div class="flex items-start gap-3">
                                @if ($url = $post->featuredImageUrl())
                                    <img src="{{ $url }}" alt="" class="size-12 rounded-md object-cover" />
                                @else
                                    <div class="flex size-12 items-center justify-center rounded-md bg-zinc-200 text-zinc-400 dark:bg-zinc-800">
                                        <flux:icon name="document-text" class="size-5" />
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.blog.posts.edit', $post->id) }}" wire:navigate class="font-semibold text-zinc-900 hover:text-emerald-500 dark:text-zinc-100">{{ $post->title }}</a>
                                        @if ($post->is_featured)<flux:icon name="star" class="size-3 text-amber-500" />@endif
                                    </div>
                                    @if ($post->excerpt)<div class="line-clamp-1 text-xs text-zinc-500">{{ $post->excerpt }}</div>@endif
                                    @if ($post->tags->isNotEmpty())
                                        <div class="mt-1 flex flex-wrap gap-1">
                                            @foreach ($post->tags->take(3) as $t)
                                                <span class="text-[10px] text-emerald-500">#{{ $t->name }}</span>
                                            @endforeach
                                            @if ($post->tags->count() > 3)<span class="text-[10px] text-zinc-400">+{{ $post->tags->count() - 3 }}</span>@endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            @if ($post->category)
                                <flux:badge size="sm" color="zinc">{{ $post->category->name }}</flux:badge>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <button wire:click="togglePublish({{ $post->id }})" class="cursor-pointer">
                                <flux:badge size="sm" :color="match ($post->status) {
                                    'published' => 'emerald',
                                    'scheduled' => 'blue',
                                    'archived'  => 'zinc',
                                    default     => 'amber',
                                }">{{ ucfirst($post->status) }}</flux:badge>
                            </button>
                        </td>
                        <td class="px-4 py-3 text-zinc-500">
                            {{ $post->published_at?->format('M d, Y') ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-zinc-500">{{ number_format($post->views_count) }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-1">
                                <flux:button size="sm" variant="ghost" :icon="$post->is_featured ? 'star' : 'star'" wire:click="toggleFeatured({{ $post->id }})" :title="__('Toggle featured')" />
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('admin.blog.posts.edit', $post->id)" wire:navigate />
                                <flux:button size="sm" variant="danger" icon="trash" wire:click="delete({{ $post->id }})" wire:confirm="{{ __('Move this post to trash?') }}" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-12 text-center text-zinc-500">{{ __('No posts match your filters.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $posts->links() }}</div>
</div>
