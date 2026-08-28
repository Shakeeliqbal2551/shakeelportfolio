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
                <div id="post-body-editor" wire:ignore></div>

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

@assets
<link rel="stylesheet" href="{{ asset('css/quill.snow.css') }}">
<link rel="stylesheet" href="{{ asset('css/atom-one-dark.min.css') }}">
<script src="{{ asset('js/highlight.min.js') }}"></script>
<script src="{{ asset('js/quill.js') }}"></script>
@endassets

<style global>
    .ql-toolbar.ql-snow {
        border-color: rgb(212 212 216 / 1);
        border-radius: 0.5rem 0.5rem 0 0;
    }
    :is(.dark) .ql-toolbar.ql-snow {
        border-color: rgb(63 63 70 / 1);
    }
    :is(.dark) .ql-toolbar.ql-snow .ql-stroke { stroke: rgb(228 228 231 / 1); }
    :is(.dark) .ql-toolbar.ql-snow .ql-fill { fill: rgb(228 228 231 / 1); }
    :is(.dark) .ql-toolbar.ql-snow .ql-picker-label { color: rgb(228 228 231 / 1); }
    .ql-container.ql-snow {
        border-color: rgb(212 212 216 / 1);
        border-radius: 0 0 0.5rem 0.5rem;
        font-size: 15px;
    }
    :is(.dark) .ql-container.ql-snow {
        border-color: rgb(63 63 70 / 1);
    }
    #post-body-editor .ql-editor {
        min-height: 16rem;
        background: white;
        color: rgb(24 24 27 / 1);
    }
    :is(.dark) #post-body-editor .ql-editor {
        background: rgb(63 63 70 / 1);
        color: white;
    }
    #post-body-editor .ql-editor.ql-blank::before {
        color: rgb(113 113 122 / 1);
        font-style: normal;
    }
    #post-body-editor .ql-editor h1 { font-size: 1.5em; font-weight: 600; }
    #post-body-editor .ql-editor h2 { font-size: 1.3em; font-weight: 600; }
    #post-body-editor .ql-editor blockquote { border-left: 3px solid rgb(212 212 216 / 1); padding-left: 1em; color: rgb(113 113 122 / 1); }
    #post-body-editor .ql-editor .ql-code-block-container {
        background: #282c34;
        border-radius: 0.5rem;
        padding: 1em;
        margin-bottom: 1em;
        overflow-x: auto;
    }
    #post-body-editor .ql-editor .ql-code-block {
        color: #abb2bf;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 0.9em;
        white-space: pre;
    }
</style>

@script
<script>
    const editorEl = document.getElementById('post-body-editor');
    const hiddenInput = document.getElementById('post-body');

    const quill = new Quill(editorEl, {
        theme: 'snow',
        placeholder: 'Write your post…',
        modules: {
            syntax: { hljs: window.hljs },
            toolbar: [
                [{ header: [2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                ['blockquote', 'code-block'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['link', 'image'],
                ['clean'],
            ],
        },
    });

    // Read the initial value from $wire directly rather than the hidden
    // input's DOM value — Livewire hydrates wire:model bindings via JS
    // after page load rather than rendering a value="" attribute, so the
    // input's .value can still be empty at the moment this script runs.
    if ($wire.body) {
        quill.clipboard.dangerouslyPasteHTML($wire.body);
    }

    quill.on('text-change', function () {
        // quill.root is the actual editable content div (.ql-editor);
        // its innerHTML is used instead of getSemanticHTML() because the
        // semantic serializer strips code-block syntax-highlighting spans
        // down to a bare <pre data-language>, losing highlighting on save.
        // But root.innerHTML still contains Quill's own UI-only controls
        // (the code-block language picker <select>, marked "ql-ui") which
        // must be stripped before persisting — they're editor chrome, not
        // post content, and the sibling .ql-tooltip (link/embed popover)
        // lives outside root entirely so it's already excluded here.
        const clone = quill.root.cloneNode(true);
        clone.querySelectorAll('.ql-ui').forEach((el) => el.remove());

        hiddenInput.value = clone.innerHTML;
        hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
    });
</script>
@endscript
</div>
