<?php

use App\Models\ProfilePhoto;
use App\Services\MediaService;
use App\Support\PortfolioContext;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new
#[Layout('layouts.app')]
#[Title('Admin · Profile Photos')]
class extends Component {
    use WithFileUploads;

    public array $newPhotos = [];

    #[Computed]
    public function photos()
    {
        return PortfolioContext::current()
            ?->profilePhotos()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get() ?? collect();
    }

    public function rules(): array
    {
        $maxKb = config('media.max_upload_kb', 8192);

        return [
            'newPhotos'   => ['array', 'max:20'],
            'newPhotos.*' => ['image', 'mimes:jpg,jpeg,png,webp', "max:$maxKb"],
        ];
    }

    public function upload(MediaService $media): void
    {
        $this->validate();

        if (empty($this->newPhotos)) {
            return;
        }

        $portfolio = PortfolioContext::current();
        $next = ($portfolio->profilePhotos()->max('sort_order') ?? 0) + 10;
        $count = count($this->newPhotos);

        foreach ($this->newPhotos as $file) {
            $path = $media->store($file, 'profile_photos');

            ProfilePhoto::create([
                'portfolio_id' => $portfolio->id,
                'path'         => $path,
                'alt'          => $portfolio->display_name.' portrait',
                'sort_order'   => $next,
                'is_active'    => true,
            ]);

            $next += 10;
        }

        $this->newPhotos = [];
        PortfolioContext::clear();

        \Flux\Flux::toast(
            heading: __('Uploaded'),
            text: $count.' '.($count === 1 ? __('photo added.') : __('photos added.')),
            variant: 'success',
        );
    }

    public function toggleActive(int $id): void
    {
        $photo = ProfilePhoto::findOrFail($id);
        $photo->update(['is_active' => ! $photo->is_active]);
    }

    public function moveUp(int $id): void
    {
        $photo = ProfilePhoto::findOrFail($id);
        $above = ProfilePhoto::where('portfolio_id', $photo->portfolio_id)
            ->where('sort_order', '<', $photo->sort_order)
            ->orderByDesc('sort_order')
            ->first();

        if ($above) {
            $tmp = $photo->sort_order;
            $photo->update(['sort_order' => $above->sort_order]);
            $above->update(['sort_order' => $tmp]);
        }
    }

    public function moveDown(int $id): void
    {
        $photo = ProfilePhoto::findOrFail($id);
        $below = ProfilePhoto::where('portfolio_id', $photo->portfolio_id)
            ->where('sort_order', '>', $photo->sort_order)
            ->orderBy('sort_order')
            ->first();

        if ($below) {
            $tmp = $photo->sort_order;
            $photo->update(['sort_order' => $below->sort_order]);
            $below->update(['sort_order' => $tmp]);
        }
    }

    public function updateAlt(int $id, string $alt): void
    {
        ProfilePhoto::findOrFail($id)->update(['alt' => $alt]);
    }

    public function delete(int $id, MediaService $media): void
    {
        $photo = ProfilePhoto::findOrFail($id);
        $media->delete($photo->path);
        $photo->delete();
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-2">
    <div>
        <flux:heading size="xl">{{ __('Profile Photos') }}</flux:heading>
        <flux:subheading>
            {{ __('Upload one or more portraits. The right-side panel of your portfolio picks an active one at random on every page load.') }}
        </flux:subheading>
    </div>

    {{-- Upload zone --}}
    <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <flux:heading size="md">{{ __('Add new photos') }}</flux:heading>
                <flux:text size="sm" class="text-zinc-500">
                    {{ __('JPG, PNG or WebP. Up to') }} {{ round(config('media.max_upload_kb', 8192) / 1024, 1) }} MB {{ __('each. You can pick multiple at once.') }}
                </flux:text>
            </div>
            @if (! empty($newPhotos))
                <flux:button variant="primary" wire:click="upload" wire:loading.attr="disabled" icon="cloud-arrow-up">
                    <span wire:loading.remove wire:target="upload">{{ __('Upload') }} ({{ count($newPhotos) }})</span>
                    <span wire:loading wire:target="upload">{{ __('Uploading…') }}</span>
                </flux:button>
            @endif
        </div>

        <flux:input type="file" wire:model="newPhotos" accept="image/*" multiple />
        @error('newPhotos.*') <flux:error>{{ $message }}</flux:error> @enderror

        @if (! empty($newPhotos))
            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                @foreach ($newPhotos as $i => $file)
                    <div class="relative overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700" wire:key="new-{{ $i }}">
                        <img src="{{ $file->temporaryUrl() }}" class="aspect-[3/4] w-full object-cover" />
                        <div class="absolute right-1 top-1">
                            <flux:badge color="emerald" size="sm">{{ __('Pending') }}</flux:badge>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Existing photos --}}
    <div>
        <div class="mb-4 flex items-center justify-between">
            <flux:heading size="lg">{{ __('Your portraits') }} ({{ $this->photos->count() }})</flux:heading>
            <flux:text size="sm" class="text-zinc-500">
                {{ __('Active photos:') }} <span class="font-semibold text-emerald-500">{{ $this->photos->where('is_active', true)->count() }}</span>
            </flux:text>
        </div>

        @if ($this->photos->isEmpty())
            <div class="rounded-xl border border-dashed border-zinc-300 p-12 text-center text-zinc-500 dark:border-zinc-700">
                {{ __('No photos yet — upload your first portrait above.') }}
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($this->photos as $photo)
                    <div class="group overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900" wire:key="photo-{{ $photo->id }}">
                        <div class="relative">
                            <img src="{{ $photo->url() }}" alt="{{ $photo->alt }}" class="aspect-[3/4] w-full object-cover transition group-hover:scale-105" />

                            @if (! $photo->is_active)
                                <div class="absolute inset-0 flex items-center justify-center bg-black/60">
                                    <flux:badge color="zinc">{{ __('Hidden') }}</flux:badge>
                                </div>
                            @endif

                            <div class="absolute left-2 top-2">
                                <flux:badge size="sm" color="zinc">#{{ $photo->sort_order }}</flux:badge>
                            </div>
                        </div>

                        <div class="space-y-3 p-3">
                            <flux:input
                                size="sm"
                                value="{{ $photo->alt }}"
                                wire:change="updateAlt({{ $photo->id }}, $event.target.value)"
                                placeholder="Alt text"
                            />

                            <div class="flex items-center justify-between gap-1">
                                <div class="flex gap-1">
                                    <flux:button size="sm" variant="ghost" wire:click="moveUp({{ $photo->id }})" icon="arrow-up" :title="__('Move up')" />
                                    <flux:button size="sm" variant="ghost" wire:click="moveDown({{ $photo->id }})" icon="arrow-down" :title="__('Move down')" />
                                </div>
                                <div class="flex gap-1">
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        wire:click="toggleActive({{ $photo->id }})"
                                        :icon="$photo->is_active ? 'eye-slash' : 'eye'"
                                        :title="$photo->is_active ? __('Hide') : __('Show')"
                                    />
                                    <flux:modal.trigger :name="'delete-photo-'.$photo->id">
                                        <flux:button size="sm" variant="danger" icon="trash" :title="__('Delete')" />
                                    </flux:modal.trigger>
                                </div>
                            </div>
                        </div>

                        <flux:modal :name="'delete-photo-'.$photo->id" class="md:w-96">
                            <div class="space-y-4">
                                <flux:heading size="lg">{{ __('Delete this photo?') }}</flux:heading>
                                <flux:text>{{ __('This is permanent. The file will also be removed from storage.') }}</flux:text>
                                <div class="flex justify-end gap-2">
                                    <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                                    <flux:button variant="danger" wire:click="delete({{ $photo->id }})">{{ __('Delete') }}</flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
