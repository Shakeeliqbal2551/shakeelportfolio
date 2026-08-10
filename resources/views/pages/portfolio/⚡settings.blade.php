<?php

use App\Models\Portfolio;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component {
    public Portfolio $portfolio;

    public string $slug = '';
    public string $theme = 'default';
    public string $site_title = '';
    public ?string $meta_description = null;

    public ?string $hero_badge_text = null;
    public ?string $hero_subtitle = null;
    public ?string $hero_title = null;
    public ?string $hero_title_accent = null;
    public ?string $hero_description = null;
    public ?string $hero_cta_primary_label = null;
    public ?string $hero_cta_primary_href = null;
    public ?string $hero_cta_secondary_label = null;
    public ?string $hero_cta_secondary_href = null;

    /** @var array<int, string> */
    public array $hero_reassurance_items = ['', '', ''];

    /** @var array<int, array{label: string, value: string}> */
    public array $hero_stats = [
        ['label' => '', 'value' => ''],
        ['label' => '', 'value' => ''],
        ['label' => '', 'value' => ''],
        ['label' => '', 'value' => ''],
    ];

    public ?string $trust_label = null;
    public ?string $trust_flags = null;
    public ?string $contact_email = null;
    public ?string $whatsapp_number = null;

    public function mount(): void
    {
        $portfolio = Auth::user()->portfolio;

        abort_if(! $portfolio, 404);

        $this->portfolio = $portfolio;

        $this->slug = $portfolio->slug;
        $this->theme = $portfolio->theme;
        $this->site_title = $portfolio->site_title;
        $this->meta_description = $portfolio->meta_description;

        $this->hero_badge_text = $portfolio->hero_badge_text;
        $this->hero_subtitle = $portfolio->hero_subtitle;
        $this->hero_title = $portfolio->hero_title;
        $this->hero_title_accent = $portfolio->hero_title_accent;
        $this->hero_description = $portfolio->hero_description;
        $this->hero_cta_primary_label = $portfolio->hero_cta_primary_label;
        $this->hero_cta_primary_href = $portfolio->hero_cta_primary_href;
        $this->hero_cta_secondary_label = $portfolio->hero_cta_secondary_label;
        $this->hero_cta_secondary_href = $portfolio->hero_cta_secondary_href;

        $items = $portfolio->hero_reassurance_items ?? [];
        for ($i = 0; $i < 3; $i++) {
            $this->hero_reassurance_items[$i] = $items[$i] ?? '';
        }

        $stats = $portfolio->hero_stats ?? [];
        for ($i = 0; $i < 4; $i++) {
            $this->hero_stats[$i] = [
                'label' => $stats[$i]['label'] ?? '',
                'value' => $stats[$i]['value'] ?? '',
            ];
        }

        $this->trust_label = $portfolio->trust_label;
        $this->trust_flags = $portfolio->trust_flags;
        $this->contact_email = $portfolio->contact_email;
        $this->whatsapp_number = $portfolio->whatsapp_number;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'slug' => 'required|string|max:255|alpha_dash|unique:portfolios,slug,'.$this->portfolio->id,
            'site_title' => 'required|string|max:255',
            'meta_description' => 'nullable|string',
            'hero_badge_text' => 'nullable|string|max:255',
            'hero_subtitle' => 'nullable|string|max:255',
            'hero_title' => 'nullable|string|max:255',
            'hero_title_accent' => 'nullable|string|max:255',
            'hero_description' => 'nullable|string',
            'hero_cta_primary_label' => 'nullable|string|max:255',
            'hero_cta_primary_href' => 'nullable|string|max:255',
            'hero_cta_secondary_label' => 'nullable|string|max:255',
            'hero_cta_secondary_href' => 'nullable|string|max:255',
            'hero_reassurance_items' => 'array|size:3',
            'hero_reassurance_items.*' => 'nullable|string|max:255',
            'hero_stats' => 'array|size:4',
            'hero_stats.*.label' => 'nullable|string|max:255',
            'hero_stats.*.value' => 'nullable|string|max:255',
            'trust_label' => 'nullable|string|max:255',
            'trust_flags' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'whatsapp_number' => 'nullable|string|max:255',
        ]);

        $validated['hero_reassurance_items'] = array_values(array_filter(
            $this->hero_reassurance_items,
            fn ($item) => filled($item)
        ));

        $this->portfolio->update($validated);

        $this->dispatch('portfolio-settings-updated');
    }
}; ?>

<section class="w-full">
    <flux:heading size="xl">{{ __('Portfolio Settings') }}</flux:heading>
    <flux:subheading>{{ __('Manage your portfolio site, hero section, and contact details') }}</flux:subheading>

    <form wire:submit="save" class="my-6 w-full max-w-3xl space-y-10">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('General') }}</flux:heading>

            <flux:input wire:model="slug" :label="__('Slug')" type="text" required />
            <flux:input wire:model="site_title" :label="__('Site Title')" type="text" required />
            <flux:textarea wire:model="meta_description" :label="__('Meta Description')" rows="3" />

            <flux:select :label="__('Theme')" disabled>
                <flux:select.option selected>{{ __('Default') }}</flux:select.option>
            </flux:select>
        </div>

        <flux:separator />

        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Hero Section') }}</flux:heading>

            <flux:input wire:model="hero_badge_text" :label="__('Badge Text')" type="text" />
            <flux:input wire:model="hero_subtitle" :label="__('Subtitle')" type="text" />
            <flux:input wire:model="hero_title" :label="__('Title')" type="text" />
            <flux:input wire:model="hero_title_accent" :label="__('Title Accent')" type="text" />
            <flux:textarea wire:model="hero_description" :label="__('Description')" rows="3" />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:input wire:model="hero_cta_primary_label" :label="__('Primary CTA Label')" type="text" />
                <flux:input wire:model="hero_cta_primary_href" :label="__('Primary CTA URL')" type="text" />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:input wire:model="hero_cta_secondary_label" :label="__('Secondary CTA Label')" type="text" />
                <flux:input wire:model="hero_cta_secondary_href" :label="__('Secondary CTA URL')" type="text" />
            </div>
        </div>

        <flux:separator />

        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Hero Reassurance Items') }}</flux:heading>
            <flux:subheading>{{ __('Three short reassurance phrases shown under the hero CTAs') }}</flux:subheading>

            @foreach ($hero_reassurance_items as $index => $item)
                <flux:input wire:model="hero_reassurance_items.{{ $index }}" :label="__('Item :n', ['n' => $index + 1])" type="text" />
            @endforeach
        </div>

        <flux:separator />

        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Hero Stats') }}</flux:heading>
            <flux:subheading>{{ __('Four stat tiles shown in the hero section') }}</flux:subheading>

            @foreach ($hero_stats as $index => $stat)
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:input wire:model="hero_stats.{{ $index }}.label" :label="__('Stat :n Label', ['n' => $index + 1])" type="text" />
                    <flux:input wire:model="hero_stats.{{ $index }}.value" :label="__('Stat :n Value', ['n' => $index + 1])" type="text" />
                </div>
            @endforeach
        </div>

        <flux:separator />

        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Trust & Contact') }}</flux:heading>

            <flux:input wire:model="trust_label" :label="__('Trust Label')" type="text" />
            <flux:input wire:model="trust_flags" :label="__('Trust Flags')" type="text" />
            <flux:input wire:model="contact_email" :label="__('Contact Email')" type="email" />
            <flux:input wire:model="whatsapp_number" :label="__('WhatsApp Number')" type="text" />
        </div>

        <div class="flex items-center gap-4">
            <flux:button variant="primary" type="submit">
                {{ __('Save') }}
            </flux:button>

            <x-action-message class="me-3" on="portfolio-settings-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</section>
