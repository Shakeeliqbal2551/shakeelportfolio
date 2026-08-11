<?php

use App\Models\Post;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

new class extends Component {
    public int $portfolioId;
    public string $portfolioSlug;

    public ?int $deletingId = null;

    public function mount(): void
    {
        $portfolio = Auth::user()->portfolio;

        abort_if(! $portfolio, 404);

        $this->portfolioId = $portfolio->id;
        $this->portfolioSlug = $portfolio->slug;
    }

    public function getPostsProperty()
    {
        return Post::where('portfolio_id', $this->portfolioId)
            ->orderBy('published_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->modal('post-delete')->show();
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $post = Post::where('portfolio_id', $this->portfolioId)->where('id', $this->deletingId)->first();

            if ($post) {
                if ($post->featured_image_path) {
                    Storage::disk('public')->delete($post->featured_image_path);
                }

                $post->delete();
            }
        }

        $this->deletingId = null;
        $this->modal('post-delete')->close();
        $this->dispatch('post-saved');
    }
}; ?>

<section class="w-full">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Blog') }}</flux:heading>
            <flux:subheading>{{ __('Manage your portfolio blog posts') }}</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" :href="route('portfolio.posts.create')" wire:navigate>
            {{ __('Add New') }}
        </flux:button>
    </div>

    <div class="my-6 flex items-center gap-4">
        <x-action-message on="post-saved">{{ __('Saved.') }}</x-action-message>
    </div>

    <div class="space-y-3">
        @forelse ($this->posts as $post)
            <div class="flex items-center justify-between gap-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="flex items-center gap-3">
                    @if ($post->featured_image_path)
                        <img src="{{ $post->featured_image_url }}" alt="{{ $post->title }}" class="h-12 w-12 rounded-lg object-cover">
                    @endif
                    <div>
                        <div class="flex items-center gap-2">
                            <flux:heading>{{ $post->title }}</flux:heading>
                            @if ($post->isPublished())
                                <flux:badge size="sm" color="green">{{ __('Published') }}</flux:badge>
                            @else
                                <flux:badge size="sm" color="zinc">{{ __('Draft') }}</flux:badge>
                            @endif
                        </div>
                        @if ($post->isPublished())
                            <flux:text class="text-zinc-500">{{ __('Published') }} {{ $post->published_at->format('M j, Y') }}</flux:text>
                        @else
                            <flux:text class="text-zinc-500">{{ \Illuminate\Support\Str::limit($post->excerpt, 80) }}</flux:text>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-1">
                    @if ($post->isPublished())
                        <flux:button size="sm" variant="subtle" icon="arrow-top-right-on-square" :href="route('portfolio.blog.show', [$this->portfolioSlug, $post->slug])" target="_blank" />
                    @endif
                    <flux:button size="sm" variant="subtle" icon="pencil-square" :href="route('portfolio.posts.edit', $post)" wire:navigate />
                    <flux:button size="sm" variant="subtle" icon="trash" wire:click="confirmDelete({{ $post->id }})" />
                </div>
            </div>
        @empty
            <flux:text class="text-zinc-500">{{ __('No blog posts yet.') }}</flux:text>
        @endforelse
    </div>

    <flux:modal name="post-delete" class="max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete post?') }}</flux:heading>
            <flux:text>{{ __('This action cannot be undone.') }}</flux:text>

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="delete">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
