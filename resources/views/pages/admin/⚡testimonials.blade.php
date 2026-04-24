<?php

use App\Models\Testimonial;
use App\Services\MediaService;
use App\Support\PortfolioContext;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new
#[Layout('layouts.app')]
#[Title('Admin · Testimonials')]
class extends Component {
    use WithFileUploads;

    public ?int $editingId = null;
    public string $quote = '';
    public string $author = '';
    public string $role = '';
    public string $company = '';
    public string $country = '';
    public string $source_url = '';
    public ?int $rating = 5;
    public bool $is_visible = true;
    public bool $is_featured = false;
    public int $sort_order = 0;
    public ?string $avatar_path = null;
    public $avatar_upload = null;

    #[Computed]
    public function items()
    {
        return PortfolioContext::current()
            ?->testimonials()
            ->orderBy('sort_order')
            ->get() ?? collect();
    }

    public function rules(): array
    {
        $maxKb = config('media.max_upload_kb', 8192);

        return [
            'quote'         => ['required', 'string'],
            'author'        => ['required', 'string', 'max:160'],
            'role'          => ['nullable', 'string', 'max:160'],
            'company'       => ['nullable', 'string', 'max:160'],
            'country'       => ['nullable', 'string', 'max:80'],
            'source_url'    => ['nullable', 'url', 'max:255'],
            'rating'        => ['nullable', 'integer', 'min:1', 'max:5'],
            'is_visible'    => ['boolean'],
            'is_featured'   => ['boolean'],
            'sort_order'    => ['integer', 'min:0', 'max:9999'],
            'avatar_upload' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', "max:$maxKb"],
        ];
    }

    public function startCreate(): void
    {
        $this->reset(['editingId', 'quote', 'author', 'role', 'company', 'country', 'source_url', 'avatar_path', 'avatar_upload']);
        $this->rating = 5;
        $this->is_visible = true;
        $this->is_featured = false;
        $this->sort_order = (int) ((PortfolioContext::current()?->testimonials()->max('sort_order') ?? 0) + 10);
        \Flux\Flux::modal('testimonial-editor')->show();
    }

    public function startEdit(int $id): void
    {
        $t = Testimonial::findOrFail($id);
        $this->editingId   = $t->id;
        $this->quote       = $t->quote;
        $this->author      = $t->author;
        $this->role        = $t->role ?? '';
        $this->company     = $t->company ?? '';
        $this->country     = $t->country ?? '';
        $this->source_url  = $t->source_url ?? '';
        $this->rating      = $t->rating;
        $this->is_visible  = (bool) $t->is_visible;
        $this->is_featured = (bool) $t->is_featured;
        $this->sort_order  = $t->sort_order;
        $this->avatar_path = $t->avatar_path;
        $this->avatar_upload = null;
        \Flux\Flux::modal('testimonial-editor')->show();
    }

    public function save(MediaService $media): void
    {
        $data = $this->validate();
        $portfolio = PortfolioContext::current();

        if ($this->avatar_upload) {
            if ($this->avatar_path) $media->delete($this->avatar_path);
            $this->avatar_path = $media->store($this->avatar_upload, 'testimonials');
        }

        unset($data['avatar_upload']);
        $data['avatar_path'] = $this->avatar_path;

        if ($this->editingId) {
            Testimonial::where('id', $this->editingId)->update($data);
        } else {
            Testimonial::create($data + ['portfolio_id' => $portfolio->id]);
        }

        \Flux\Flux::modal('testimonial-editor')->close();
        \Flux\Flux::toast(heading: __('Saved'), text: __('Testimonial saved.'), variant: 'success');
        $this->reset(['editingId', 'avatar_upload']);
    }

    public function deleteAvatar(MediaService $media): void
    {
        if ($this->avatar_path) {
            $media->delete($this->avatar_path);
            $this->avatar_path = null;
        }
    }

    public function toggleVisible(int $id): void
    {
        $t = Testimonial::findOrFail($id);
        $t->update(['is_visible' => ! $t->is_visible]);
    }

    public function delete(int $id, MediaService $media): void
    {
        $t = Testimonial::findOrFail($id);
        if ($t->avatar_path) $media->delete($t->avatar_path);
        $t->delete();
        \Flux\Flux::toast(heading: __('Deleted'), text: __('Testimonial removed.'), variant: 'success');
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-2">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Testimonials') }}</flux:heading>
            <flux:subheading>{{ __('Client quotes shown in the Testimonials grid. Verified-source links boost trust.') }}</flux:subheading>
        </div>
        <flux:button variant="primary" wire:click="startCreate" icon="plus">{{ __('New Testimonial') }}</flux:button>
    </div>

    <div class="grid gap-3 md:grid-cols-2">
        @forelse ($this->items as $t)
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900" wire:key="tst-{{ $t->id }}">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 flex-1 items-start gap-3">
                        @if ($t->avatar_path)
                            <img src="{{ $t->avatarUrl() }}" class="size-10 rounded-full object-cover" />
                        @else
                            <div class="flex size-10 items-center justify-center rounded-full bg-zinc-200 text-xs font-bold text-zinc-500 dark:bg-zinc-800">
                                {{ collect(explode(' ', $t->author))->take(2)->map(fn ($w) => substr($w, 0, 1))->join('') }}
                            </div>
                        @endif
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <flux:heading size="sm">{{ $t->author }}</flux:heading>
                                @if ($t->is_featured) <flux:icon name="star" class="size-3 text-amber-500" /> @endif
                                @if (! $t->is_visible) <flux:badge size="sm" color="zinc">{{ __('Hidden') }}</flux:badge> @endif
                            </div>
                            <flux:text size="xs" class="text-zinc-500">{{ $t->role }}{{ $t->company ? ', '.$t->company : '' }}{{ $t->country ? ' · '.$t->country : '' }}</flux:text>
                            @if ($t->rating)
                                <div class="mt-1 text-xs text-amber-500">{{ str_repeat('★', $t->rating) }}<span class="text-zinc-300">{{ str_repeat('★', 5 - $t->rating) }}</span></div>
                            @endif
                            <p class="mt-2 line-clamp-3 text-sm italic text-zinc-600 dark:text-zinc-400">"{{ $t->quote }}"</p>
                        </div>
                    </div>
                    <div class="flex shrink-0 gap-1">
                        <flux:button size="sm" variant="ghost" :icon="$t->is_visible ? 'eye-slash' : 'eye'" wire:click="toggleVisible({{ $t->id }})" />
                        <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="startEdit({{ $t->id }})" />
                        <flux:button size="sm" variant="danger" icon="trash" wire:click="delete({{ $t->id }})" wire:confirm="{{ __('Delete this testimonial?') }}" />
                    </div>
                </div>
            </div>
        @empty
            <div class="md:col-span-2 rounded-xl border border-dashed border-zinc-300 p-12 text-center text-zinc-500 dark:border-zinc-700">
                {{ __('No testimonials yet.') }}
            </div>
        @endforelse
    </div>

    <flux:modal name="testimonial-editor" class="md:w-[640px]">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? __('Edit testimonial') : __('New testimonial') }}</flux:heading>

            <flux:textarea wire:model="quote" label="{{ __('Quote') }}" rows="5" required />

            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="author"  label="{{ __('Author name') }}" required />
                <flux:input wire:model="role"    label="{{ __('Role / title') }}" placeholder="Tech Lead" />
                <flux:input wire:model="company" label="{{ __('Company') }}" />
                <flux:input wire:model="country" label="{{ __('Country') }}" placeholder="USA" />
                <flux:input type="url" wire:model="source_url" label="{{ __('Source URL') }}" description="{{ __('LinkedIn / Upwork link to verify.') }}" class="md:col-span-2" />
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <flux:select wire:model="rating" label="{{ __('Rating') }}">
                    @for ($r = 5; $r >= 1; $r--)
                        <option value="{{ $r }}">{{ str_repeat('★', $r) }} ({{ $r }})</option>
                    @endfor
                </flux:select>
                <flux:input type="number" wire:model="sort_order" label="{{ __('Sort order') }}" min="0" />
                <div class="flex flex-col gap-2 pt-6">
                    <flux:switch wire:model="is_visible" label="{{ __('Visible') }}" />
                    <flux:switch wire:model="is_featured" label="{{ __('Featured') }}" />
                </div>
            </div>

            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading size="sm" class="mb-2">{{ __('Avatar (optional)') }}</flux:heading>

                @if ($avatar_path && ! $avatar_upload)
                    <div class="mb-3 flex items-center gap-3">
                        <img src="{{ app(\App\Services\MediaService::class)->url($avatar_path) }}" class="size-14 rounded-full object-cover" />
                        <flux:button size="sm" variant="danger" wire:click="deleteAvatar" icon="trash" type="button">{{ __('Remove') }}</flux:button>
                    </div>
                @elseif ($avatar_upload)
                    <div class="mb-3 flex items-center gap-3">
                        <img src="{{ $avatar_upload->temporaryUrl() }}" class="size-14 rounded-full object-cover" />
                        <flux:badge color="emerald" size="sm">{{ __('Pending upload') }}</flux:badge>
                    </div>
                @endif

                <flux:input type="file" wire:model="avatar_upload" accept="image/*" />
                @error('avatar_upload') <flux:error>{{ $message }}</flux:error> @enderror
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
