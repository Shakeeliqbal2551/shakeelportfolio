<?php

use App\Models\Portfolio;
use App\Services\CompressesUploadedImages;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public Portfolio $portfolio;

    public string $slug = '';
    public string $theme = 'default';
    public string $site_title = '';
    public ?string $meta_description = null;
    public $logo = null;
    public ?string $existingLogoPath = null;
    public $favicon = null;
    public ?string $existingFaviconPath = null;
    public $og_image = null;
    public ?string $existingOgImagePath = null;
    public ?string $blog_meta_description = null;

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
    public ?string $smtp_host = null;
    public ?int $smtp_port = null;
    public ?string $smtp_username = null;
    public string $smtp_password = '';
    public string $smtp_encryption = 'tls';
    public ?string $smtp_from_address = null;
    public ?string $smtp_from_name = null;

    public function mount(): void
    {
        $portfolio = Auth::user()->portfolio;

        abort_if(! $portfolio, 404);

        $this->portfolio = $portfolio;

        $this->slug = $portfolio->slug;
        $this->theme = $portfolio->theme;
        $this->site_title = $portfolio->site_title;
        $this->meta_description = $portfolio->meta_description;
        $this->existingLogoPath = $portfolio->logo_path;
        $this->existingFaviconPath = $portfolio->favicon_path;
        $this->existingOgImagePath = $portfolio->og_image_path;
        $this->blog_meta_description = $portfolio->blog_meta_description;

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
        $this->smtp_host = $portfolio->smtp_host;
        $this->smtp_port = $portfolio->smtp_port;
        $this->smtp_username = $portfolio->smtp_username;
        $this->smtp_encryption = $portfolio->smtp_encryption ?: 'tls';
        $this->smtp_from_address = $portfolio->smtp_from_address;
        $this->smtp_from_name = $portfolio->smtp_from_name;
    }

    public function removeOgImage(): void
    {
        if ($this->portfolio->og_image_path) {
            Storage::disk('public')->delete($this->portfolio->og_image_path);
            $this->portfolio->update(['og_image_path' => null]);
        }

        $this->existingOgImagePath = null;
        $this->og_image = null;
    }

    public function removeLogo(): void
    {
        if ($this->portfolio->logo_path) {
            Storage::disk('public')->delete($this->portfolio->logo_path);
            $this->portfolio->update(['logo_path' => null]);
        }

        $this->existingLogoPath = null;
        $this->logo = null;
    }

    public function removeFavicon(): void
    {
        if ($this->portfolio->favicon_path) {
            Storage::disk('public')->delete($this->portfolio->favicon_path);
            $this->portfolio->update(['favicon_path' => null]);
        }

        $this->existingFaviconPath = null;
        $this->favicon = null;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'slug' => 'required|string|max:255|alpha_dash|unique:portfolios,slug,'.$this->portfolio->id,
            'site_title' => 'required|string|max:60',
            'meta_description' => 'nullable|string|max:160',
            'logo' => 'nullable|image|max:2048',
            'favicon' => 'nullable|image|max:1024',
            'og_image' => 'nullable|image|max:4096',
            'blog_meta_description' => 'nullable|string|max:160',
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
            'smtp_host' => 'nullable|string|max:255',
            'smtp_port' => 'nullable|required_with:smtp_host|integer|min:1|max:65535',
            'smtp_username' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string|max:1024',
            'smtp_encryption' => 'required_with:smtp_host|in:tls,ssl,none',
            'smtp_from_address' => 'nullable|required_with:smtp_host|email|max:255',
            'smtp_from_name' => 'nullable|string|max:255',
        ]);

        $validated['hero_reassurance_items'] = array_values(array_filter(
            $this->hero_reassurance_items,
            fn ($item) => filled($item)
        ));

        unset($validated['logo'], $validated['favicon'], $validated['og_image']);

        if ($this->smtp_password === '') {
            unset($validated['smtp_password']);
        }

        if ($this->logo) {
            if ($this->portfolio->logo_path) {
                Storage::disk('public')->delete($this->portfolio->logo_path);
            }

            $validated['logo_path'] = CompressesUploadedImages::store($this->logo, "portfolios/{$this->portfolio->id}/logo");
        }

        if ($this->favicon) {
            if ($this->portfolio->favicon_path) {
                Storage::disk('public')->delete($this->portfolio->favicon_path);
            }

            $validated['favicon_path'] = CompressesUploadedImages::store($this->favicon, "portfolios/{$this->portfolio->id}/favicon");
        }

        if ($this->og_image) {
            if ($this->portfolio->og_image_path) {
                Storage::disk('public')->delete($this->portfolio->og_image_path);
            }

            $validated['og_image_path'] = CompressesUploadedImages::store($this->og_image, "portfolios/{$this->portfolio->id}/og");
        }

        $this->portfolio->update($validated);
        $this->existingLogoPath = $this->portfolio->logo_path;
        $this->existingFaviconPath = $this->portfolio->favicon_path;
        $this->existingOgImagePath = $this->portfolio->og_image_path;
        $this->logo = null;
        $this->favicon = null;
        $this->og_image = null;

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
            <flux:input
                wire:model.live="site_title"
                :label="__('Site Title')"
                :description="__(':count/60 characters — shown as the page title in search results', ['count' => strlen($site_title)])"
                type="text"
                required
            />
            <flux:textarea
                wire:model.live="meta_description"
                :label="__('Meta Description')"
                :description="__(':count/160 characters — shown as the search result snippet for the homepage', ['count' => strlen((string) $meta_description)])"
                rows="3"
            />
            <flux:textarea
                wire:model.live="blog_meta_description"
                :label="__('Blog Meta Description')"
                :description="__(':count/160 characters — shown as the search result snippet for the blog index. Falls back to a generic line if left blank.', ['count' => strlen((string) $blog_meta_description)])"
                rows="3"
            />

            <flux:select :label="__('Theme')" disabled>
                <flux:select.option selected>{{ __('Default') }}</flux:select.option>
            </flux:select>
        </div>

        <flux:separator />

        <div class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Favicon') }}</flux:heading>
                <flux:subheading>{{ __('Shown in browser tabs and bookmarks for your domain.') }}</flux:subheading>
            </div>

            @if ($existingFaviconPath && ! $favicon)
                <img src="{{ \App\Models\Portfolio::resolveFileUrl($existingFaviconPath) }}" alt="{{ __('Current favicon') }}" class="h-12 w-12 rounded border border-zinc-200 object-contain dark:border-zinc-700">
            @elseif ($favicon)
                <img src="{{ $favicon->temporaryUrl() }}" alt="{{ __('New favicon preview') }}" class="h-12 w-12 rounded border border-zinc-200 object-contain dark:border-zinc-700">
            @endif

            <flux:input wire:model="favicon" :label="__('Upload Favicon')" type="file" accept="image/png,image/jpeg,image/webp,image/gif"
                :description="__('Recommended: a square image, at least 64×64px, up to 1 MB.')" />

            @if ($existingFaviconPath)
                <flux:button size="sm" variant="subtle" wire:click="removeFavicon">{{ __('Remove current favicon') }}</flux:button>
            @endif
        </div>

        <flux:separator />

        <div class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Site Logo') }}</flux:heading>
                <flux:subheading>{{ __('Shown in the site header. Your first-name initial is shown when no logo is uploaded.') }}</flux:subheading>
            </div>

            @if ($existingLogoPath && ! $logo)
                <img src="{{ \App\Models\Portfolio::resolveFileUrl($existingLogoPath) }}" alt="{{ __('Current site logo') }}" class="h-16 w-16 rounded-full border border-zinc-200 object-contain dark:border-zinc-700">
            @elseif ($logo)
                <img src="{{ $logo->temporaryUrl() }}" alt="{{ __('New site logo preview') }}" class="h-16 w-16 rounded-full border border-zinc-200 object-contain dark:border-zinc-700">
            @else
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-cyan-500 text-2xl font-bold uppercase text-zinc-950">
                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(trim((string) auth()->user()->name), 0, 1)) ?: '?' }}
                </div>
            @endif

            <flux:input wire:model="logo" :label="__('Upload Logo')" type="file" accept="image/*"
                :description="__('Recommended: a square PNG, JPG, or WebP image up to 2 MB.')" />

            @if ($existingLogoPath)
                <flux:button size="sm" variant="subtle" wire:click="removeLogo">{{ __('Remove current logo') }}</flux:button>
            @endif
        </div>

        <flux:separator />

        <div class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Social Share Image') }}</flux:heading>
                <flux:subheading>{{ __('Shown when your site is shared on Facebook, LinkedIn, X, WhatsApp, and Slack — and used as the fallback image for AI/search results.') }}</flux:subheading>
            </div>

            @if ($existingOgImagePath && ! $og_image)
                <img src="{{ \App\Models\Portfolio::resolveFileUrl($existingOgImagePath) }}" alt="{{ __('Current social share image') }}" class="w-full max-w-md rounded-lg border border-zinc-200 object-cover dark:border-zinc-700" style="aspect-ratio: 1200/630;">
            @elseif ($og_image)
                <img src="{{ $og_image->temporaryUrl() }}" alt="{{ __('New social share image preview') }}" class="w-full max-w-md rounded-lg border border-zinc-200 object-cover dark:border-zinc-700" style="aspect-ratio: 1200/630;">
            @else
                <div class="flex w-full max-w-md items-center justify-center rounded-lg border border-dashed border-zinc-300 p-8 text-center dark:border-zinc-600" style="aspect-ratio: 1200/630;">
                    <flux:text class="text-zinc-500">{{ __('No custom image yet — a generic branded image is used until you upload one.') }}</flux:text>
                </div>
            @endif

            <flux:input
                wire:model="og_image"
                :label="__('Upload Image')"
                type="file"
                accept="image/*"
                description="{{ __('Recommended size: 1200×630px (landscape). Square or portrait photos get cropped awkwardly when shared on social media — a wide banner with your name/title works best.') }}"
            />

            @if ($existingOgImagePath)
                <flux:button size="sm" variant="subtle" wire:click="removeOgImage">{{ __('Remove current image') }}</flux:button>
            @endif
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

        <flux:separator />

        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Contact Form Email (SMTP)') }}</flux:heading>
                <flux:subheading>{{ __('Messages are delivered to the Contact Email above. Enter the outgoing SMTP details supplied by your email provider.') }}</flux:subheading>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:input wire:model="smtp_host" :label="__('SMTP Host')" type="text" placeholder="smtp.gmail.com" />
                <flux:input wire:model="smtp_port" :label="__('SMTP Port')" type="number" placeholder="587" />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:input wire:model="smtp_username" :label="__('SMTP Username')" type="text" autocomplete="off" />
                <flux:input wire:model="smtp_password" :label="__('SMTP Password / App Password')" type="password" autocomplete="new-password"
                    :placeholder="$portfolio->smtp_password ? __('Leave blank to keep existing password') : ''" />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:select wire:model="smtp_encryption" :label="__('Encryption')">
                    <flux:select.option value="tls">TLS / STARTTLS</flux:select.option>
                    <flux:select.option value="ssl">SSL</flux:select.option>
                    <flux:select.option value="none">{{ __('None') }}</flux:select.option>
                </flux:select>
                <flux:input wire:model="smtp_from_address" :label="__('Sender Email')" type="email" placeholder="you@example.com" />
            </div>

            <flux:input wire:model="smtp_from_name" :label="__('Sender Name')" type="text" :placeholder="$site_title" />
            <flux:text class="text-zinc-500">{{ __('For Gmail, enable two-step verification and use a Google App Password instead of your normal password.') }}</flux:text>
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
