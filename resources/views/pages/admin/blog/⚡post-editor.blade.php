<?php

use App\Models\BlogTag;
use App\Models\Post;
use App\Services\MediaService;
use App\Support\PortfolioContext;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new
#[Layout('layouts.app')]
#[Title('Admin · Post Editor')]
class extends Component {
    use WithFileUploads;

    public ?Post $post = null;
    public string $activeTab = 'content';

    // Content
    public string $title = '';
    public string $slug = '';
    public string $excerpt = '';
    public string $content = '';
    public string $content_format = 'markdown';

    // Media
    public ?string $featured_image_path = null;
    public string $featured_image_alt = '';
    public $featured_upload = null;

    // Taxonomy
    public ?int $blog_category_id = null;
    public array $selectedTags = [];
    public string $newTagInput = '';

    // Status
    public string $status = 'draft';
    public ?string $published_at = null;
    public bool $is_featured = false;
    public bool $allow_comments = false;

    // SEO
    public string $seo_title = '';
    public string $seo_description = '';
    public string $canonical_url = '';

    // Preview
    public bool $previewMode = false;

    public function mount(?Post $post = null): void
    {
        $portfolio = PortfolioContext::current();
        abort_unless($portfolio, 404);

        if ($post && $post->exists) {
            abort_unless($post->portfolio_id === $portfolio->id, 403);
            $this->post = $post->load('tags');
            $this->loadFromPost();
        } else {
            $this->status = Post::STATUS_DRAFT;
            $this->content_format = 'markdown';
        }
    }

    protected function loadFromPost(): void
    {
        $p = $this->post;

        $this->title               = $p->title ?? '';
        $this->slug                = $p->slug ?? '';
        $this->excerpt             = $p->excerpt ?? '';
        $this->content             = $p->content ?? '';
        $this->content_format      = $p->content_format ?? 'markdown';
        $this->featured_image_path = $p->featured_image_path;
        $this->featured_image_alt  = $p->featured_image_alt ?? '';
        $this->blog_category_id    = $p->blog_category_id;
        $this->selectedTags        = $p->tags->pluck('id')->all();
        $this->status              = $p->status ?? Post::STATUS_DRAFT;
        $this->published_at        = $p->published_at?->format('Y-m-d\TH:i');
        $this->is_featured         = (bool) $p->is_featured;
        $this->allow_comments      = (bool) $p->allow_comments;
        $this->seo_title           = $p->seo_title ?? '';
        $this->seo_description     = $p->seo_description ?? '';
        $this->canonical_url       = $p->canonical_url ?? '';
    }

    #[Computed]
    public function categories()
    {
        return PortfolioContext::current()?->blogCategories()->orderBy('sort_order')->get() ?? collect();
    }

    #[Computed]
    public function tags()
    {
        return PortfolioContext::current()?->blogTags()->orderBy('name')->get() ?? collect();
    }

    #[Computed]
    public function renderedContent(): string
    {
        if (! trim($this->content)) return '';

        if ($this->content_format === 'markdown') {
            return Str::markdown($this->content, ['html_input' => 'allow', 'allow_unsafe_links' => false]);
        }

        return $this->content;
    }

    #[Computed]
    public function readingTime(): int
    {
        $words = str_word_count(strip_tags($this->renderedContent));
        return max(1, (int) ceil($words / 200));
    }

    public function rules(): array
    {
        $portfolio = PortfolioContext::current();
        $existingId = $this->post?->id;
        $maxKb = config('media.max_upload_kb', 8192);

        return [
            'title'               => ['required', 'string', 'max:200'],
            'slug'                => ['required', 'string', 'alpha_dash', 'max:200', "unique:posts,slug,{$existingId},id,portfolio_id,{$portfolio?->id}"],
            'excerpt'             => ['nullable', 'string', 'max:500'],
            'content'             => ['nullable', 'string'],
            'content_format'      => ['required', 'string', 'in:markdown,html'],
            'featured_image_alt'  => ['nullable', 'string', 'max:200'],
            'featured_upload'     => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', "max:$maxKb"],
            'blog_category_id'    => ['nullable', 'integer', 'exists:blog_categories,id'],
            'selectedTags'        => ['array'],
            'selectedTags.*'      => ['integer', 'exists:blog_tags,id'],
            'status'              => ['required', 'string', 'in:draft,scheduled,published,archived'],
            'published_at'        => ['nullable', 'date'],
            'is_featured'         => ['boolean'],
            'allow_comments'      => ['boolean'],
            'seo_title'           => ['nullable', 'string', 'max:255'],
            'seo_description'     => ['nullable', 'string', 'max:500'],
            'canonical_url'       => ['nullable', 'url', 'max:255'],
        ];
    }

    public function updatedTitle(string $value): void
    {
        if (! $this->post && empty($this->slug)) {
            $this->slug = Str::slug($value);
        }
    }

    public function addTag(): void
    {
        $name = trim($this->newTagInput);
        if (! $name) return;

        $portfolio = PortfolioContext::current();
        $slug = Str::slug($name);

        $tag = BlogTag::firstOrCreate(
            ['portfolio_id' => $portfolio->id, 'slug' => $slug],
            ['name' => $name]
        );

        if (! in_array($tag->id, $this->selectedTags)) {
            $this->selectedTags[] = $tag->id;
        }

        $this->newTagInput = '';
        unset($this->tags); // refresh computed
    }

    public function deleteFeaturedImage(MediaService $media): void
    {
        if ($this->featured_image_path) {
            $media->delete($this->featured_image_path);
            $this->featured_image_path = null;
            $this->featured_upload = null;
        }
    }

    public function save(MediaService $media)
    {
        $data = $this->validate();
        $portfolio = PortfolioContext::current();

        // Featured image upload
        if ($this->featured_upload) {
            if ($this->featured_image_path) $media->delete($this->featured_image_path);
            $this->featured_image_path = $media->store($this->featured_upload, 'blog_featured');
            $this->featured_upload = null;
        }

        // Auto-set published_at when going to published with no date set
        if ($data['status'] === Post::STATUS_PUBLISHED && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        $payload = [
            'title'               => $data['title'],
            'slug'                => $data['slug'],
            'excerpt'             => $data['excerpt'] ?: null,
            'content'             => $data['content'] ?: null,
            'content_format'      => $data['content_format'],
            'featured_image_path' => $this->featured_image_path,
            'featured_image_alt'  => $data['featured_image_alt'] ?: null,
            'blog_category_id'    => $data['blog_category_id'] ?: null,
            'status'              => $data['status'],
            'published_at'        => $data['published_at'] ?: null,
            'is_featured'         => $data['is_featured'],
            'allow_comments'      => $data['allow_comments'],
            'seo_title'           => $data['seo_title'] ?: null,
            'seo_description'     => $data['seo_description'] ?: null,
            'canonical_url'       => $data['canonical_url'] ?: null,
            'reading_time_minutes'=> $this->readingTime,
        ];

        if ($this->post) {
            $this->post->update($payload);
        } else {
            $payload['portfolio_id'] = $portfolio->id;
            $payload['author_id']    = auth()->id();
            $this->post = Post::create($payload);
        }

        $this->post->tags()->sync($data['selectedTags'] ?? []);

        \Flux\Flux::toast(heading: __('Saved'), text: __('Post saved.'), variant: 'success');

        if (! request()->routeIs('admin.blog.posts.edit')) {
            return redirect()->route('admin.blog.posts.edit', $this->post->id);
        }

        $this->post->load('tags');
    }

    public function publishNow(): void
    {
        $this->status = Post::STATUS_PUBLISHED;
        if (! $this->published_at) {
            $this->published_at = now()->format('Y-m-d\TH:i');
        }
        $this->save(app(MediaService::class));
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-2">

    {{-- Header --}}
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <flux:link :href="route('admin.blog.posts')" wire:navigate icon-leading="arrow-left">{{ __('Back to posts') }}</flux:link>
            <flux:heading size="xl" class="mt-1">{{ $post ? $post->title : __('New Post') }}</flux:heading>
            <flux:subheading>
                @if ($post)
                    <code class="rounded bg-zinc-100 px-1 py-0.5 text-xs dark:bg-zinc-800">/{{ $slug }}</code>
                    · {{ __('Reading time') }}: {{ $this->readingTime }} {{ __('min') }}
                @else
                    {{ __('Draft a new article. Save once, then upload images and refine.') }}
                @endif
            </flux:subheading>
        </div>
        <div class="flex flex-wrap gap-2">
            <flux:button wire:click="$toggle('previewMode')" :icon="$previewMode ? 'pencil-square' : 'eye'">
                {{ $previewMode ? __('Edit') : __('Preview') }}
            </flux:button>
            @if ($status !== 'published')
                <flux:button variant="primary" wire:click="publishNow" icon="paper-airplane">{{ __('Publish now') }}</flux:button>
            @endif
            <flux:button :variant="$status === 'published' ? 'filled' : 'primary'" wire:click="save" icon="check">
                <span wire:loading.remove wire:target="save">{{ __('Save') }}</span>
                <span wire:loading wire:target="save">{{ __('Saving…') }}</span>
            </flux:button>
        </div>
    </div>

    <x-admin.tabs :active="$activeTab" :tabs="[
        ['name' => 'content',  'label' => __('Content'),  'icon' => 'document-text'],
        ['name' => 'media',    'label' => __('Media'),    'icon' => 'photo'],
        ['name' => 'taxonomy', 'label' => __('Category & tags'), 'icon' => 'tag'],
        ['name' => 'publish',  'label' => __('Publish'),  'icon' => 'paper-airplane'],
        ['name' => 'seo',      'label' => __('SEO'),      'icon' => 'magnifying-glass'],
    ]" />

    <div>

        {{-- CONTENT --}}
        <div @class(['grid gap-5 lg:grid-cols-3', 'hidden' => $activeTab !== 'content'])>
            <div class="space-y-5 lg:col-span-2">
                <flux:input wire:model.live.debounce.500ms="title" label="{{ __('Title') }}" required />
                <flux:input wire:model="slug" label="{{ __('URL slug') }}" required />
                <flux:textarea wire:model="excerpt" label="{{ __('Excerpt') }}" rows="2" description="{{ __('1-2 lines used in listings and meta description fallback.') }}" />

                @if ($previewMode)
                    <div class="prose prose-zinc dark:prose-invert max-w-none rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                        @if (trim($content))
                            {!! $this->renderedContent !!}
                        @else
                            <p class="text-zinc-400">{{ __('Nothing to preview yet.') }}</p>
                        @endif
                    </div>
                @else
                    <div>
                        <div class="mb-1 flex items-center justify-between">
                            <flux:label>{{ __('Body') }}</flux:label>
                            <flux:select wire:model.live="content_format" size="sm" class="w-32">
                                <option value="markdown">{{ __('Markdown') }}</option>
                                <option value="html">{{ __('HTML') }}</option>
                            </flux:select>
                        </div>
                        <textarea
                            wire:model="content"
                            rows="22"
                            placeholder="# Heading{{"\n\n"}}Write your post in Markdown…{{"\n\n"}}- bullet{{"\n"}}- list{{"\n\n"}}**bold** and *italic*"
                            class="block w-full rounded-lg border border-zinc-300 bg-white p-4 font-mono text-sm leading-relaxed text-zinc-900 focus:border-emerald-500 focus:ring-emerald-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                            style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;"
                        ></textarea>
                        <flux:description>{{ __('Markdown supported: headings, **bold**, *italic*, [links](url), `code`, lists, > quotes, ![images](url).') }}</flux:description>
                    </div>
                @endif
            </div>

            {{-- Sidebar tips --}}
            <div class="space-y-4">
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:heading size="sm" class="mb-2">{{ __('Quick stats') }}</flux:heading>
                    <dl class="space-y-1 text-sm">
                        <div class="flex justify-between"><dt class="text-zinc-500">{{ __('Words') }}</dt><dd>{{ str_word_count(strip_tags($this->renderedContent)) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-zinc-500">{{ __('Characters') }}</dt><dd>{{ Str::length($content) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-zinc-500">{{ __('Reading time') }}</dt><dd>{{ $this->readingTime }} {{ __('min') }}</dd></div>
                        <div class="flex justify-between"><dt class="text-zinc-500">{{ __('Status') }}</dt><dd><flux:badge size="sm" :color="match ($status) {'published'=>'emerald','scheduled'=>'blue','archived'=>'zinc',default=>'amber'}">{{ ucfirst($status) }}</flux:badge></dd></div>
                    </dl>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:heading size="sm" class="mb-2">{{ __('Markdown cheatsheet') }}</flux:heading>
                    <pre class="whitespace-pre-wrap text-xs leading-relaxed text-zinc-600 dark:text-zinc-400"># H1
## H2
**bold**  *italic*
[link](https://…)
`inline code`
```
code block
```
- bullet
1. numbered
&gt; quote
![alt](image.jpg)</pre>
                </div>
            </div>
        </div>

        {{-- MEDIA --}}
        <div @class(['space-y-5', 'hidden' => $activeTab !== 'media'])>
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md" class="mb-3">{{ __('Featured image') }}</flux:heading>
                <flux:text size="sm" class="mb-4 text-zinc-500">{{ __('Used as the cover image on listings and as the Open Graph preview.') }}</flux:text>

                @if ($featured_image_path && ! $featured_upload)
                    <div class="mb-4">
                        <img src="{{ app(\App\Services\MediaService::class)->url($featured_image_path) }}" class="max-h-72 w-auto rounded-lg object-cover" />
                        <flux:button size="sm" variant="danger" wire:click="deleteFeaturedImage" icon="trash" class="mt-2">{{ __('Remove image') }}</flux:button>
                    </div>
                @elseif ($featured_upload)
                    <div class="mb-4">
                        <img src="{{ $featured_upload->temporaryUrl() }}" class="max-h-72 w-auto rounded-lg object-cover" />
                        <flux:badge color="emerald" size="sm" class="mt-2">{{ __('Pending — save the post to commit') }}</flux:badge>
                    </div>
                @endif

                <flux:input type="file" wire:model="featured_upload" accept="image/*" />
                @error('featured_upload') <flux:error>{{ $message }}</flux:error> @enderror
                <div wire:loading wire:target="featured_upload" class="mt-2 text-sm text-zinc-500">{{ __('Uploading…') }}</div>

                <div class="mt-4">
                    <flux:input wire:model="featured_image_alt" label="{{ __('Alt text') }}" placeholder="{{ __('Describe the image for screen readers') }}" />
                </div>
            </div>
        </div>

        {{-- TAXONOMY --}}
        <div @class(['grid gap-5 md:grid-cols-2', 'hidden' => $activeTab !== 'taxonomy'])>
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md" class="mb-3">{{ __('Category') }}</flux:heading>
                <flux:select wire:model="blog_category_id">
                    <option value="">{{ __('— Uncategorised —') }}</option>
                    @foreach ($this->categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </flux:select>
                <flux:description class="mt-2">
                    {{ __('Need a new one?') }}
                    <flux:link :href="route('admin.blog.categories')" wire:navigate>{{ __('Manage categories') }} →</flux:link>
                </flux:description>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md" class="mb-3">{{ __('Tags') }}</flux:heading>
                <div class="mb-3 flex gap-2">
                    <flux:input wire:model="newTagInput" placeholder="{{ __('Add or pick from existing') }}" class="flex-1" wire:keydown.enter.prevent="addTag" />
                    <flux:button size="sm" wire:click="addTag" icon="plus">{{ __('Add') }}</flux:button>
                </div>

                @if ($this->tags->isEmpty())
                    <flux:text size="sm" class="text-zinc-500">{{ __('No tags yet. Type one above and press Enter.') }}</flux:text>
                @else
                    <div class="flex flex-wrap gap-2">
                        @foreach ($this->tags as $tag)
                            <label class="cursor-pointer rounded-full border border-zinc-300 px-3 py-1 text-sm transition hover:border-emerald-500 dark:border-zinc-600 has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-500/10">
                                <input type="checkbox" wire:model="selectedTags" value="{{ $tag->id }}" class="sr-only" />
                                <span class="text-zinc-700 dark:text-zinc-300">#{{ $tag->name }}</span>
                            </label>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- PUBLISH --}}
        <div @class(['space-y-5', 'hidden' => $activeTab !== 'publish'])>
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="grid gap-4 md:grid-cols-2">
                    <flux:select wire:model.live="status" label="{{ __('Status') }}">
                        <option value="draft">{{ __('Draft (private)') }}</option>
                        <option value="scheduled">{{ __('Scheduled (auto-publish)') }}</option>
                        <option value="published">{{ __('Published') }}</option>
                        <option value="archived">{{ __('Archived') }}</option>
                    </flux:select>
                    <flux:input type="datetime-local" wire:model="published_at" label="{{ __('Publish date / time') }}" />
                </div>
                <div class="mt-4 flex flex-col gap-3">
                    <flux:switch wire:model="is_featured"     label="{{ __('Featured (highlighted on blog index)') }}" />
                    <flux:switch wire:model="allow_comments"  label="{{ __('Allow comments') }}" />
                </div>
            </div>
        </div>

        {{-- SEO --}}
        <div @class(['space-y-5', 'hidden' => $activeTab !== 'seo'])>
            <flux:input    wire:model="seo_title"        label="{{ __('SEO title') }}" description="{{ __('Falls back to post title.') }}" />
            <flux:textarea wire:model="seo_description"  label="{{ __('SEO description') }}" rows="3" description="{{ __('Falls back to excerpt.') }}" />
            <flux:input    wire:model="canonical_url"    type="url" label="{{ __('Canonical URL') }}" placeholder="https://yoursite.com/post-slug" />
        </div>
    </div>

    <div class="sticky bottom-4 mt-4 flex justify-end">
        <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled" icon="check" class="shadow-lg">
            <span wire:loading.remove wire:target="save">{{ __('Save post') }}</span>
            <span wire:loading wire:target="save">{{ __('Saving…') }}</span>
        </flux:button>
    </div>
</div>
