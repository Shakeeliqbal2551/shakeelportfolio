<?php

use App\Services\MediaService;
use App\Support\PortfolioContext;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new
#[Layout('layouts.app')]
#[Title('Admin · Site Settings')]
class extends Component {
    use WithFileUploads;

    public string $activeTab = 'hero';

    // Hero
    public ?string $hero_badge = null;
    public ?string $hero_subtitle = null;
    public ?string $hero_title_html = null;
    public ?string $hero_description = null;
    public ?string $hero_cta_primary_label = null;
    public ?string $hero_cta_primary_url = null;
    public ?string $hero_cta_secondary_label = null;
    public ?string $hero_cta_secondary_url = null;
    public array $hero_reassurance = [];
    public array $hero_flags = [];

    // Stats
    public ?string $stat_years = null;
    public ?string $stat_projects = null;
    public ?string $stat_clients = null;
    public ?string $stat_countries = null;

    // About
    public ?string $about_subtitle = null;
    public ?string $about_title = null;
    public ?string $about_description = null;
    public ?string $about_location = null;
    public ?string $about_phone = null;
    public ?string $about_email = null;
    public ?string $about_whatsapp = null;
    public ?string $about_linkedin = null;
    public ?string $about_resume_path = null;
    public $resume_upload = null;

    // Contact
    public ?string $contact_subtitle = null;
    public ?string $contact_title = null;
    public ?string $contact_description = null;
    public ?string $contact_address = null;

    // SEO
    public ?string $seo_title = null;
    public ?string $seo_description = null;
    public ?string $seo_keywords = null;
    public ?string $seo_og_image = null;
    public $og_image_upload = null;
    public ?string $canonical_url = null;

    public function mount(): void
    {
        $portfolio = PortfolioContext::current();
        abort_unless($portfolio, 404);

        $settings = $portfolio->settings()->firstOrCreate(['portfolio_id' => $portfolio->id]);

        // Map every column on the model to a public property of the same name.
        $stringFields = [
            'hero_badge', 'hero_subtitle', 'hero_title_html', 'hero_description',
            'hero_cta_primary_label', 'hero_cta_primary_url',
            'hero_cta_secondary_label', 'hero_cta_secondary_url',
            'stat_years', 'stat_projects', 'stat_clients', 'stat_countries',
            'about_subtitle', 'about_title', 'about_description',
            'about_location', 'about_phone', 'about_email', 'about_whatsapp',
            'about_linkedin', 'about_resume_path',
            'contact_subtitle', 'contact_title', 'contact_description', 'contact_address',
            'seo_title', 'seo_description', 'seo_keywords', 'seo_og_image', 'canonical_url',
        ];

        foreach ($stringFields as $field) {
            $this->{$field} = $settings->{$field};
        }

        $this->hero_reassurance = $settings->hero_reassurance ?? [];
        $this->hero_flags       = $settings->hero_flags ?? [];
    }

    protected function rules(): array
    {
        $maxKb = config('media.max_upload_kb', 8192);

        return [
            'hero_badge'                 => ['nullable', 'string', 'max:140'],
            'hero_subtitle'              => ['nullable', 'string', 'max:160'],
            'hero_title_html'            => ['nullable', 'string', 'max:500'],
            'hero_description'           => ['nullable', 'string'],
            'hero_cta_primary_label'     => ['nullable', 'string', 'max:80'],
            'hero_cta_primary_url'       => ['nullable', 'string', 'max:255'],
            'hero_cta_secondary_label'   => ['nullable', 'string', 'max:80'],
            'hero_cta_secondary_url'     => ['nullable', 'string', 'max:255'],
            'hero_reassurance'           => ['array'],
            'hero_reassurance.*'         => ['nullable', 'string', 'max:120'],
            'hero_flags'                 => ['array'],
            'hero_flags.*'               => ['nullable', 'string', 'max:8'],
            'stat_years'                 => ['nullable', 'string', 'max:16'],
            'stat_projects'              => ['nullable', 'string', 'max:16'],
            'stat_clients'               => ['nullable', 'string', 'max:16'],
            'stat_countries'             => ['nullable', 'string', 'max:16'],
            'about_subtitle'             => ['nullable', 'string', 'max:160'],
            'about_title'                => ['nullable', 'string', 'max:160'],
            'about_description'          => ['nullable', 'string'],
            'about_location'             => ['nullable', 'string', 'max:160'],
            'about_phone'                => ['nullable', 'string', 'max:60'],
            'about_email'                => ['nullable', 'email', 'max:160'],
            'about_whatsapp'             => ['nullable', 'string', 'max:60'],
            'about_linkedin'             => ['nullable', 'url', 'max:255'],
            'resume_upload'              => ['nullable', 'file', 'mimes:pdf,doc,docx', "max:$maxKb"],
            'contact_subtitle'           => ['nullable', 'string', 'max:160'],
            'contact_title'              => ['nullable', 'string', 'max:160'],
            'contact_description'        => ['nullable', 'string'],
            'contact_address'            => ['nullable', 'string', 'max:255'],
            'seo_title'                  => ['nullable', 'string', 'max:255'],
            'seo_description'            => ['nullable', 'string', 'max:500'],
            'seo_keywords'               => ['nullable', 'string', 'max:500'],
            'og_image_upload'            => ['nullable', 'image', "max:$maxKb"],
            'canonical_url'              => ['nullable', 'url', 'max:255'],
        ];
    }

    public function addReassurance(): void
    {
        $this->hero_reassurance[] = '';
    }

    public function removeReassurance(int $i): void
    {
        unset($this->hero_reassurance[$i]);
        $this->hero_reassurance = array_values($this->hero_reassurance);
    }

    public function addFlag(): void
    {
        $this->hero_flags[] = '';
    }

    public function removeFlag(int $i): void
    {
        unset($this->hero_flags[$i]);
        $this->hero_flags = array_values($this->hero_flags);
    }

    public function save(MediaService $media): void
    {
        $this->validate();

        // File uploads
        if ($this->resume_upload) {
            if ($this->about_resume_path) {
                $media->delete($this->about_resume_path);
            }
            $this->about_resume_path = $media->store($this->resume_upload, 'documents');
            $this->resume_upload = null;
        }

        if ($this->og_image_upload) {
            if ($this->seo_og_image) {
                $media->delete($this->seo_og_image);
            }
            $this->seo_og_image = $media->store($this->og_image_upload, 'misc');
            $this->og_image_upload = null;
        }

        $reassurance = array_values(array_filter($this->hero_reassurance, fn ($v) => trim((string) $v) !== ''));
        $flags       = array_values(array_filter($this->hero_flags,       fn ($v) => trim((string) $v) !== ''));

        $portfolio = PortfolioContext::current();
        $portfolio->settings()->update([
            'hero_badge'                 => $this->hero_badge,
            'hero_subtitle'              => $this->hero_subtitle,
            'hero_title_html'            => $this->hero_title_html,
            'hero_description'           => $this->hero_description,
            'hero_cta_primary_label'     => $this->hero_cta_primary_label,
            'hero_cta_primary_url'       => $this->hero_cta_primary_url,
            'hero_cta_secondary_label'   => $this->hero_cta_secondary_label,
            'hero_cta_secondary_url'     => $this->hero_cta_secondary_url,
            'hero_reassurance'           => $reassurance,
            'hero_flags'                 => $flags,
            'stat_years'                 => $this->stat_years,
            'stat_projects'              => $this->stat_projects,
            'stat_clients'               => $this->stat_clients,
            'stat_countries'             => $this->stat_countries,
            'about_subtitle'             => $this->about_subtitle,
            'about_title'                => $this->about_title,
            'about_description'          => $this->about_description,
            'about_location'             => $this->about_location,
            'about_phone'                => $this->about_phone,
            'about_email'                => $this->about_email,
            'about_whatsapp'             => $this->about_whatsapp,
            'about_linkedin'             => $this->about_linkedin,
            'about_resume_path'          => $this->about_resume_path,
            'contact_subtitle'           => $this->contact_subtitle,
            'contact_title'              => $this->contact_title,
            'contact_description'        => $this->contact_description,
            'contact_address'            => $this->contact_address,
            'seo_title'                  => $this->seo_title,
            'seo_description'            => $this->seo_description,
            'seo_keywords'               => $this->seo_keywords,
            'seo_og_image'               => $this->seo_og_image,
            'canonical_url'              => $this->canonical_url,
        ]);

        $this->hero_reassurance = $reassurance;
        $this->hero_flags       = $flags;

        PortfolioContext::clear();

        \Flux\Flux::toast(
            heading: __('Saved'),
            text: __('Site settings updated.'),
            variant: 'success',
        );
    }

    public function deleteResume(MediaService $media): void
    {
        if ($this->about_resume_path) {
            $media->delete($this->about_resume_path);
            $this->about_resume_path = null;
            PortfolioContext::current()?->settings()->update(['about_resume_path' => null]);
            PortfolioContext::clear();
        }
    }

    public function deleteOgImage(MediaService $media): void
    {
        if ($this->seo_og_image) {
            $media->delete($this->seo_og_image);
            $this->seo_og_image = null;
            PortfolioContext::current()?->settings()->update(['seo_og_image' => null]);
            PortfolioContext::clear();
        }
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-2">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Site Settings') }}</flux:heading>
            <flux:subheading>{{ __('Edit hero, about, contact, and SEO content. Changes appear on the public site immediately.') }}</flux:subheading>
        </div>
        <div class="flex gap-2">
            <flux:button :href="url('/')" target="_blank" icon="arrow-top-right-on-square">{{ __('Preview site') }}</flux:button>
            <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled" icon="check">
                <span wire:loading.remove wire:target="save">{{ __('Save changes') }}</span>
                <span wire:loading wire:target="save">{{ __('Saving…') }}</span>
            </flux:button>
        </div>
    </div>

    <x-admin.tabs :active="$activeTab" :tabs="[
        ['name' => 'hero',    'label' => __('Hero'),    'icon' => 'bolt'],
        ['name' => 'stats',   'label' => __('Stats'),   'icon' => 'chart-bar-square'],
        ['name' => 'about',   'label' => __('About'),   'icon' => 'user'],
        ['name' => 'contact', 'label' => __('Contact'), 'icon' => 'envelope'],
        ['name' => 'seo',     'label' => __('SEO'),     'icon' => 'magnifying-glass'],
    ]" />

    <div>
        {{-- HERO --}}
        <div @class(['space-y-5', 'hidden' => $activeTab !== 'hero'])>
            <flux:input wire:model="hero_badge" label="{{ __('Available-now badge') }}" description="{{ __('Small uppercase pill at the top of the hero. Leave blank to hide.') }}" placeholder="Available — Booking projects now" />

            <flux:input wire:model="hero_subtitle" label="{{ __('Subtitle (above title)') }}" placeholder="Senior Laravel Developer · Islamabad, Pakistan" />

            <flux:textarea wire:model="hero_title_html" label="{{ __('Title (HTML allowed)') }}" description="Wrap a word in &lt;span class=&quot;accent&quot;&gt;…&lt;/span&gt; for the gradient highlight." rows="3" />

            <flux:textarea wire:model="hero_description" label="{{ __('Description') }}" description="{{ __('Short paragraph below the title. HTML <strong> is allowed.') }}" rows="5" />

            <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:heading size="sm">{{ __('Primary CTA') }}</flux:heading>
                    <flux:input wire:model="hero_cta_primary_label" label="{{ __('Label') }}" placeholder="Book a Free 30-min Call" />
                    <flux:input wire:model="hero_cta_primary_url" label="{{ __('URL or anchor') }}" placeholder="#contact" />
                </div>
                <div class="space-y-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:heading size="sm">{{ __('Secondary CTA') }}</flux:heading>
                    <flux:input wire:model="hero_cta_secondary_label" label="{{ __('Label') }}" placeholder="See My Work" />
                    <flux:input wire:model="hero_cta_secondary_url" label="{{ __('URL or anchor') }}" placeholder="#portfolio" />
                </div>
            </div>

            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="mb-3 flex items-center justify-between">
                    <div>
                        <flux:heading size="sm">{{ __('Reassurance items') }}</flux:heading>
                        <flux:text size="sm" class="text-zinc-500">{{ __('Small checkmark items shown under the CTA buttons.') }}</flux:text>
                    </div>
                    <flux:button size="sm" wire:click="addReassurance" icon="plus">{{ __('Add') }}</flux:button>
                </div>
                <div class="space-y-2">
                    @foreach ($hero_reassurance as $i => $item)
                        <div class="flex items-center gap-2" wire:key="reass-{{ $i }}">
                            <flux:input wire:model="hero_reassurance.{{ $i }}" placeholder="No obligation" class="flex-1" />
                            <flux:button size="sm" variant="danger" wire:click="removeReassurance({{ $i }})" icon="trash" />
                        </div>
                    @endforeach
                    @if (empty($hero_reassurance))
                        <flux:text size="sm" class="text-zinc-500">{{ __('No items yet. Click Add.') }}</flux:text>
                    @endif
                </div>
            </div>

            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="mb-3 flex items-center justify-between">
                    <div>
                        <flux:heading size="sm">{{ __('Trusted-by flags') }}</flux:heading>
                        <flux:text size="sm" class="text-zinc-500">{{ __('Country flag emojis shown after the stats row.') }}</flux:text>
                    </div>
                    <flux:button size="sm" wire:click="addFlag" icon="plus">{{ __('Add flag') }}</flux:button>
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach ($hero_flags as $i => $flag)
                        <div class="flex items-center gap-1" wire:key="flag-{{ $i }}">
                            <flux:input wire:model="hero_flags.{{ $i }}" placeholder="🇺🇸" class="w-20 text-center" />
                            <flux:button size="sm" variant="ghost" wire:click="removeFlag({{ $i }})" icon="x-mark" />
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- STATS --}}
        <div @class(['grid gap-4 md:grid-cols-2 lg:grid-cols-4', 'hidden' => $activeTab !== 'stats'])>
            <flux:input wire:model="stat_years"     label="{{ __('Years experience') }}" placeholder="6+" />
            <flux:input wire:model="stat_projects"  label="{{ __('Projects shipped') }}" placeholder="20+" />
            <flux:input wire:model="stat_clients"   label="{{ __('Happy clients') }}"    placeholder="15+" />
            <flux:input wire:model="stat_countries" label="{{ __('Countries served') }}" placeholder="6+" />
        </div>

        {{-- ABOUT --}}
        <div @class(['space-y-5', 'hidden' => $activeTab !== 'about'])>
            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="about_subtitle" label="{{ __('Subtitle') }}"  placeholder="About Me" />
                <flux:input wire:model="about_title"    label="{{ __('Title') }}"     placeholder="A developer who thinks like a founder." />
            </div>

            <flux:textarea wire:model="about_description" label="{{ __('Description') }}" rows="6" />

            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="about_location" label="{{ __('Based in') }}"          placeholder="Islamabad, Pakistan" />
                <flux:input wire:model="about_phone"    label="{{ __('Phone') }}"             placeholder="+92 302 9865526" />
                <flux:input wire:model="about_email"    type="email" label="{{ __('Email') }}" placeholder="contact@shakeeliqbal.com" />
                <flux:input wire:model="about_whatsapp" label="{{ __('WhatsApp number (intl, no spaces)') }}" placeholder="+923029865526" />
                <flux:input wire:model="about_linkedin" type="url" label="{{ __('LinkedIn URL') }}" placeholder="https://www.linkedin.com/in/..." class="md:col-span-2" />
            </div>

            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading size="sm" class="mb-2">{{ __('Resume / CV (PDF or DOC)') }}</flux:heading>

                @if ($about_resume_path)
                    <div class="mb-3 flex items-center gap-3 rounded bg-zinc-50 p-2 dark:bg-zinc-800">
                        <flux:icon name="document-text" class="size-5 text-emerald-500" />
                        <a href="{{ app(\App\Services\MediaService::class)->url($about_resume_path) }}" target="_blank" class="flex-1 truncate text-sm text-emerald-500 hover:underline">{{ basename($about_resume_path) }}</a>
                        <flux:button size="sm" variant="danger" wire:click="deleteResume" icon="trash" />
                    </div>
                @endif

                <flux:input type="file" wire:model="resume_upload" accept=".pdf,.doc,.docx" />
                @error('resume_upload') <flux:error>{{ $message }}</flux:error> @enderror
                <div wire:loading wire:target="resume_upload" class="mt-2 text-sm text-zinc-500">{{ __('Uploading…') }}</div>
            </div>
        </div>

        {{-- CONTACT --}}
        <div @class(['space-y-5', 'hidden' => $activeTab !== 'contact'])>
            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="contact_subtitle" label="{{ __('Subtitle') }}" placeholder="Let's Connect" />
                <flux:input wire:model="contact_title"    label="{{ __('Title') }}"    placeholder="Book a Free Consultation" />
            </div>
            <flux:textarea wire:model="contact_description" label="{{ __('Description') }}" rows="5" />
            <flux:input wire:model="contact_address" label="{{ __('Address (shown beside form)') }}" placeholder="Islamabad, Pakistan" />
        </div>

        {{-- SEO --}}
        <div @class(['space-y-5', 'hidden' => $activeTab !== 'seo'])>
            <flux:input    wire:model="seo_title"       label="{{ __('SEO title') }}" description="{{ __('Used in <title> and meta. ~60 chars.') }}" />
            <flux:textarea wire:model="seo_description" label="{{ __('SEO description') }}" description="{{ __('~155 chars.') }}" rows="3" />
            <flux:textarea wire:model="seo_keywords"    label="{{ __('Keywords (comma-separated)') }}" rows="2" />
            <flux:input    wire:model="canonical_url"   type="url" label="{{ __('Canonical URL') }}" placeholder="https://shakeeliqbal.com/" />

            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading size="sm" class="mb-2">{{ __('Open Graph image') }}</flux:heading>
                <flux:text size="sm" class="mb-3 text-zinc-500">{{ __('Shown when your link is shared on social media. 1200×630 recommended.') }}</flux:text>

                @if ($seo_og_image)
                    <div class="mb-3 flex items-center gap-3 rounded bg-zinc-50 p-2 dark:bg-zinc-800">
                        <img src="{{ app(\App\Services\MediaService::class)->url($seo_og_image) }}" class="h-16 w-28 rounded object-cover" />
                        <span class="flex-1 truncate text-sm text-zinc-500">{{ basename($seo_og_image) }}</span>
                        <flux:button size="sm" variant="danger" wire:click="deleteOgImage" icon="trash" />
                    </div>
                @endif

                <flux:input type="file" wire:model="og_image_upload" accept="image/*" />
                @error('og_image_upload') <flux:error>{{ $message }}</flux:error> @enderror
                <div wire:loading wire:target="og_image_upload" class="mt-2 text-sm text-zinc-500">{{ __('Uploading…') }}</div>
            </div>
        </div>
    </div>

    <div class="sticky bottom-4 mt-4 flex justify-end">
        <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled" icon="check" class="shadow-lg">
            <span wire:loading.remove wire:target="save">{{ __('Save changes') }}</span>
            <span wire:loading wire:target="save">{{ __('Saving…') }}</span>
        </flux:button>
    </div>
</div>
