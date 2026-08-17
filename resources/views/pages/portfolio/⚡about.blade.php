<?php

use App\Models\AboutSection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public AboutSection $about;
    public int $portfolioId;

    public ?string $heading = null;
    public ?string $title = null;
    public ?string $bio = null;

    /** @var array<int, array{label: string, value: string, subvalue: string}> */
    public array $info_cards = [];

    public ?string $cta_heading = null;
    public ?string $cta_text = null;

    public $resume = null;
    public ?string $alt_text = null;

    public function mount(): void
    {
        $portfolio = Auth::user()->portfolio;

        abort_if(! $portfolio, 404);

        $this->portfolioId = $portfolio->id;
        $this->about = $portfolio->about ?? $portfolio->about()->create([]);

        $this->heading = $this->about->heading;
        $this->title = $this->about->title;
        $this->bio = $this->about->bio;
        $this->info_cards = $this->about->info_cards ?? [];
        $this->cta_heading = $this->about->cta_heading;
        $this->cta_text = $this->about->cta_text;
        $this->alt_text = $this->about->alt_text;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'heading' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
            'info_cards' => 'array',
            'info_cards.*.label' => 'nullable|string|max:255',
            'info_cards.*.value' => 'nullable|string|max:255',
            'info_cards.*.subvalue' => 'nullable|string|max:255',
            'cta_heading' => 'nullable|string|max:255',
            'cta_text' => 'nullable|string|max:255',
            'resume' => 'nullable|file|mimes:pdf|max:5120',
            'alt_text' => 'nullable|string|max:255',
        ]);

        if ($this->resume) {
            if ($this->about->resume_path) {
                Storage::disk('public')->delete($this->about->resume_path);
            }

            $validated['resume_path'] = $this->resume->store("portfolios/{$this->portfolioId}/resume", 'public');
        }
        unset($validated['resume']);

        $this->about->update($validated);

        $this->resume = null;

        $this->dispatch('about-updated');
    }
}; ?>

<section class="w-full">
    <flux:heading size="xl">{{ __('About') }}</flux:heading>
    <flux:subheading>{{ __('Manage your about section, info cards, and resume') }}</flux:subheading>

    <form wire:submit="save" class="my-6 w-full max-w-3xl space-y-10">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Content') }}</flux:heading>

            <flux:input wire:model="heading" :label="__('Heading')" type="text" />
            <flux:input wire:model="title" :label="__('Title')" type="text" />
            <flux:textarea wire:model="bio" :label="__('Bio')" rows="6" />
        </div>

        <flux:separator />

        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Info Cards') }}</flux:heading>
            <flux:subheading>{{ __('Six fixed info cards shown in the about section') }}</flux:subheading>

            @foreach ($info_cards as $index => $card)
                <div class="grid grid-cols-1 gap-4 rounded-lg border border-zinc-200 p-4 sm:grid-cols-3 dark:border-zinc-700">
                    <flux:input wire:model="info_cards.{{ $index }}.label" :label="__('Label')" type="text" readonly />
                    <flux:input wire:model="info_cards.{{ $index }}.value" :label="__('Value')" type="text" />
                    <flux:input wire:model="info_cards.{{ $index }}.subvalue" :label="__('Subvalue')" type="text" />
                </div>
            @endforeach
        </div>

        <flux:separator />

        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Call To Action') }}</flux:heading>

            <flux:input wire:model="cta_heading" :label="__('CTA Heading')" type="text" />
            <flux:input wire:model="cta_text" :label="__('CTA Text')" type="text" />
        </div>

        <flux:separator />

        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Files') }}</flux:heading>

            <div>
                <flux:input wire:model="resume" :label="__('Resume (PDF)')" type="file" accept="application/pdf" />
                @if ($about->resume_path)
                    <flux:text class="mt-2">
                        {{ __('Current file:') }}
                        <flux:link :href="$about->resume_url" target="_blank">{{ __('View resume') }}</flux:link>
                    </flux:text>
                @endif
            </div>

            <flux:input wire:model="alt_text" :label="__('Image Alt Text (for SEO)')" type="text" placeholder="{{ __('e.g. Jane Doe — Senior Laravel Developer') }}" description="{{ __('Optional — describe your profile photo for search engines and screen readers. Falls back to your name and subtitle if left blank. Manage the photo itself under Profile Photos.') }}" />
        </div>

        <div class="flex items-center gap-4">
            <flux:button variant="primary" type="submit">
                {{ __('Save') }}
            </flux:button>

            <x-action-message class="me-3" on="about-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</section>
