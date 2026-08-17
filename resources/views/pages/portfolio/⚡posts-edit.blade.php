<?php

use App\Models\Post;
use App\Services\CompressesUploadedImages;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public int $portfolioId;

    public ?int $editingId = null;

    public string $title = '';
    public string $slug = '';
    public ?string $excerpt = null;
    public string $body = '';
    public $featured_image = null;
    public ?string $existingFeaturedImagePath = null;
    public ?string $meta_title = null;
    public ?string $meta_description = null;
    public bool $publish_now = false;

    /**
     * Accepts the route's {post} segment as a plain int (rather than typing
     * the parameter as ?Post and relying on implicit route-model binding),
     * then looks it up manually scoped to the authenticated user's
     * portfolio — matching this app's existing mount() convention of
     * scoping everything through Auth::user()->portfolio.
     */
    public function mount(?int $post = null): void
    {
        $portfolio = Auth::user()->portfolio;

        abort_if(! $portfolio, 404);

        $this->portfolioId = $portfolio->id;

        if ($post !== null) {
            $postModel = Post::where('portfolio_id', $this->portfolioId)->findOrFail($post);

            $this->editingId = $postModel->id;
            $this->title = $postModel->title;
            $this->slug = $postModel->slug;
            $this->excerpt = $postModel->excerpt;
            $this->body = $postModel->body;
            $this->existingFeaturedImagePath = $postModel->featured_image_path;
            $this->meta_title = $postModel->meta_title;
            $this->meta_description = $postModel->meta_description;
            $this->publish_now = $postModel->isPublished();
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'title' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('posts')->where('portfolio_id', $this->portfolioId)->ignore($this->editingId),
            ],
            'excerpt' => 'nullable|string',
            'body' => 'required|string',
            'featured_image' => 'nullable|image|max:2048',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'publish_now' => 'boolean',
        ]);

        $validated['slug'] = $validated['slug'] ?: Str::slug($this->title);
        $validated['published_at'] = $this->publish_now ? now() : null;

        unset($validated['featured_image'], $validated['publish_now']);

        if ($this->featured_image) {
            if ($this->existingFeaturedImagePath) {
                Storage::disk('public')->delete($this->existingFeaturedImagePath);
            }

            $validated['featured_image_path'] = CompressesUploadedImages::store($this->featured_image, "portfolios/{$this->portfolioId}/posts");
        }

        if ($this->editingId) {
            $post = Post::where('portfolio_id', $this->portfolioId)->findOrFail($this->editingId);
            $post->update($validated);
        } else {
            $validated['portfolio_id'] = $this->portfolioId;
            $post = Post::create($validated);
            $this->editingId = $post->id;
        }

        $this->slug = $post->slug;

        $this->dispatch('post-saved');

        $this->redirect(route('portfolio.posts'), navigate: true);
    }
}; ?>

<div>
<section class="w-full max-w-3xl">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ $editingId ? __('Edit Post') : __('Add Post') }}</flux:heading>
            <flux:subheading>{{ __('Write and publish a blog post') }}</flux:subheading>
        </div>

        <flux:button variant="filled" :href="route('portfolio.posts')" wire:navigate>
            {{ __('Back to Blog') }}
        </flux:button>
    </div>

    <form wire:submit="save" class="my-6 space-y-6">
        <flux:input wire:model="title" :label="__('Title')" type="text" required />

        <flux:input wire:model="slug" :label="__('Slug')" type="text" description="{{ __('Leave blank to auto-generate from title') }}" />

        <flux:textarea wire:model="excerpt" :label="__('Excerpt')" rows="3" />

        <div>
            <flux:field>
                <flux:label>{{ __('Body') }}</flux:label>

                <input id="post-body" type="hidden" wire:model="body">
                <trix-editor input="post-body" class="trix-content" placeholder="{{ __('Write your post…') }}"></trix-editor>

                <flux:error name="body" />
            </flux:field>
        </div>

        <div>
            <flux:input wire:model="featured_image" :label="__('Featured Image')" type="file" accept="image/*" />
            @if ($existingFeaturedImagePath)
                <img src="{{ \App\Models\Post::resolveFileUrl($existingFeaturedImagePath) }}" alt="{{ __('Current featured image') }}" class="mt-2 h-24 w-24 rounded-lg object-cover">
            @endif
        </div>

        <flux:separator />

        <flux:input wire:model="meta_title" :label="__('Meta Title')" type="text" placeholder="{{ __('Falls back to the post title if left blank') }}" />
        <flux:textarea wire:model="meta_description" :label="__('Meta Description')" rows="3" placeholder="{{ __('Falls back to the excerpt if left blank') }}" />

        <flux:separator />

        <flux:checkbox wire:model="publish_now" :label="__('Publish now')" description="{{ __('Leave unchecked to save as a draft') }}" />

        <div class="flex items-center gap-4">
            <flux:button variant="primary" type="submit">
                {{ __('Save') }}
            </flux:button>

            <x-action-message class="me-3" on="post-saved">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</section>

<!-- Trix rich text editor (CDN — see report for rationale) -->
<link rel="stylesheet" href="https://unpkg.com/trix@2/dist/trix.css">
<script type="text/javascript" src="https://unpkg.com/trix@2/dist/trix.umd.min.js"></script>

<style>
    trix-toolbar .trix-button-group {
        border: 1px solid rgb(212 212 216 / 1);
        border-radius: 0.375rem;
        margin-bottom: 0.5rem;
    }
    :is(.dark) trix-toolbar .trix-button-group {
        border-color: rgb(63 63 70 / 1);
    }
    trix-toolbar .trix-button {
        font-size: 0.8rem;
        color: rgb(63 63 70 / 1);
        background: transparent;
        border: none;
        border-right: 1px solid rgb(212 212 216 / 1);
        cursor: pointer;
    }
    :is(.dark) trix-toolbar .trix-button {
        color: rgb(228 228 231 / 1);
        border-right-color: rgb(63 63 70 / 1);
    }
    trix-toolbar .trix-button.trix-active {
        background: rgb(228 228 231 / 1);
    }
    :is(.dark) trix-toolbar .trix-button.trix-active {
        background: rgb(63 63 70 / 1);
    }
    trix-toolbar .trix-button-group-spacer {
        flex: 1;
    }
    trix-editor.trix-content {
        min-height: 16rem;
        padding: 0.75rem 1rem;
        border: 1px solid rgb(212 212 216 / 1);
        border-radius: 0.5rem;
        background: white;
        color: rgb(24 24 27 / 1);
    }
    :is(.dark) trix-editor.trix-content {
        border-color: rgb(63 63 70 / 1);
        background: rgb(63 63 70 / 1);
        color: white;
    }
    trix-editor.trix-content:focus {
        outline: 2px solid transparent;
        outline-offset: 2px;
    }
    .trix-content h1 { font-size: 1.5em; font-weight: 600; }
    .trix-content ul { list-style-type: disc; padding-left: 1.5em; }
    .trix-content ol { list-style-type: decimal; padding-left: 1.5em; }
    .trix-content blockquote { border-left: 3px solid rgb(212 212 216 / 1); padding-left: 1em; color: rgb(113 113 122 / 1); }
</style>

<script>
document.addEventListener('trix-initialize', function (event) {
    const hiddenInput = document.getElementById('post-body');
    if (hiddenInput && event.target.inputElement === hiddenInput && hiddenInput.value) {
        event.target.editor.loadHTML(hiddenInput.value);
    }
});

document.addEventListener('trix-change', function (event) {
    const hiddenInput = document.getElementById('post-body');
    if (hiddenInput && event.target.inputElement === hiddenInput) {
        hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
    }
});
</script>
</div>
