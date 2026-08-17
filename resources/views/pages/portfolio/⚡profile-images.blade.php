<?php

use App\Models\ProfileImage;
use App\Services\CompressesUploadedImages;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public int $portfolioId;

    public $image = null;
    public ?string $alt_text = null;

    public ?int $deletingId = null;

    public function mount(): void
    {
        $portfolio = Auth::user()->portfolio;

        abort_if(! $portfolio, 404);

        $this->portfolioId = $portfolio->id;
    }

    public function getImagesProperty()
    {
        return ProfileImage::where('portfolio_id', $this->portfolioId)
            ->orderBy('sort_order')
            ->get();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'image' => 'required|image|max:2048',
            'alt_text' => 'nullable|string|max:255',
        ]);

        $maxOrder = ProfileImage::where('portfolio_id', $this->portfolioId)->max('sort_order');

        ProfileImage::create([
            'portfolio_id' => $this->portfolioId,
            'image_path' => CompressesUploadedImages::store($this->image, "portfolios/{$this->portfolioId}/profile-gallery"),
            'alt_text' => $validated['alt_text'] ?? null,
            'is_active' => false,
            'sort_order' => $maxOrder === null ? 0 : $maxOrder + 1,
        ]);

        $this->reset(['image', 'alt_text']);
        $this->dispatch('profile-image-saved');
    }

    public function toggleActive(int $id): void
    {
        $profileImage = ProfileImage::where('portfolio_id', $this->portfolioId)->findOrFail($id);
        $profileImage->update(['is_active' => ! $profileImage->is_active]);
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->modal('profile-image-delete')->show();
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $profileImage = ProfileImage::where('portfolio_id', $this->portfolioId)->where('id', $this->deletingId)->first();

            if ($profileImage) {
                Storage::disk('public')->delete($profileImage->image_path);
                $profileImage->delete();
            }
        }

        $this->deletingId = null;
        $this->modal('profile-image-delete')->close();
        $this->dispatch('profile-image-saved');
    }
}; ?>

<section class="w-full">
    <flux:heading size="xl">{{ __('Profile Photos') }}</flux:heading>
    <flux:subheading>{{ __('Upload multiple profile photos and mark the ones you want in rotation — a random active photo is shown each time your portfolio page loads.') }}</flux:subheading>

    <div class="my-6 flex items-center gap-4">
        <x-action-message on="profile-image-saved">{{ __('Saved.') }}</x-action-message>
    </div>

    <form wire:submit="save" class="max-w-2xl space-y-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
        <flux:heading size="lg">{{ __('Add a Photo') }}</flux:heading>

        <flux:input wire:model="image" :label="__('Image')" type="file" accept="image/*" />
        <flux:input wire:model="alt_text" :label="__('Alt Text (for SEO)')" type="text" placeholder="{{ __('e.g. Jane Doe — Senior Laravel Developer') }}" />

        <flux:button variant="primary" type="submit">
            {{ __('Upload') }}
        </flux:button>
    </form>

    <div class="mt-8 space-y-3">
        @forelse ($this->images as $profileImage)
            <div class="flex items-center justify-between gap-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="flex items-center gap-3">
                    <img src="{{ $profileImage->image_url }}" alt="{{ $profileImage->alt_text ?: __('Profile photo') }}" class="h-16 w-16 rounded-lg object-cover">
                    <div>
                        <div class="flex items-center gap-2">
                            <flux:text>{{ $profileImage->alt_text ?: __('Untitled photo') }}</flux:text>
                            @if ($profileImage->is_active)
                                <flux:badge size="sm" color="lime">{{ __('Active') }}</flux:badge>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <flux:switch wire:click="toggleActive({{ $profileImage->id }})" :checked="$profileImage->is_active" />
                    <flux:button size="sm" variant="subtle" icon="trash" wire:click="confirmDelete({{ $profileImage->id }})" />
                </div>
            </div>
        @empty
            <flux:text class="text-zinc-500">{{ __('No profile photos yet. Upload one above to get started.') }}</flux:text>
        @endforelse
    </div>

    <flux:modal name="profile-image-delete" class="max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete photo?') }}</flux:heading>
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
