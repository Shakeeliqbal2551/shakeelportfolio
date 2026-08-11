<div class="flex h-full w-full flex-1 flex-col gap-6 p-2">

    {{-- Header --}}
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <flux:link :href="route('admin.projects.index')" wire:navigate icon-leading="arrow-left">{{ __('Back to projects') }}</flux:link>
            <flux:heading size="xl" class="mt-1">{{ $project ? $project->title : __('New Project') }}</flux:heading>
            <flux:subheading>
                @if ($project)
                    {{ __('Editing') }} <code class="rounded bg-zinc-100 px-1 py-0.5 text-xs dark:bg-zinc-800">{{ $project->slug }}</code>
                @else
                    {{ __('Fill the basics, save, then upload images on the Media tab.') }}
                @endif
            </flux:subheading>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($project?->live_url)
                <flux:button :href="$project->live_url" target="_blank" icon="arrow-top-right-on-square">{{ __('Open live') }}</flux:button>
            @endif
            <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled" icon="check">
                <span wire:loading.remove wire:target="save">{{ __('Save project') }}</span>
                <span wire:loading wire:target="save">{{ __('Saving…') }}</span>
            </flux:button>
        </div>
    </div>

    <x-admin.tabs :active="$activeTab" :tabs="[
        ['name' => 'overview', 'label' => __('Overview'),         'icon' => 'document-text'],
        ['name' => 'details',  'label' => __('Details'),          'icon' => 'information-circle'],
        ['name' => 'tech',     'label' => __('Tech & features'),  'icon' => 'cube'],
        ['name' => 'links',    'label' => __('Links'),            'icon' => 'link'],
        ['name' => 'commerce', 'label' => __('SaaS & sale'),      'icon' => 'banknotes'],
        ['name' => 'media',    'label' => __('Media'),            'icon' => 'photo',  'disabled' => ! $project, 'badge' => $project?->images->count()],
        ['name' => 'status',   'label' => __('Status & SEO'),     'icon' => 'flag'],
    ]" />

    <div>
        {{-- OVERVIEW --}}
        <div @class(['space-y-5', 'hidden' => $activeTab !== 'overview'])>
            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model.live.debounce.500ms="title" label="{{ __('Title') }}" required />
                <flux:input wire:model="slug" label="{{ __('URL slug') }}" description="{{ __('Used to identify the project. Auto-generated from title.') }}" required />
            </div>
            <flux:input wire:model="tagline" label="{{ __('Tagline (pill on card)') }}" placeholder="US Healthcare · Remote Patient Monitoring" />
            <flux:textarea wire:model="summary" label="{{ __('Short summary (1-2 lines on the card)') }}" rows="3" />
            <flux:textarea wire:model="description" label="{{ __('Long description') }}" description="{{ __('Shown in the case-study modal. Markdown / HTML allowed.') }}" rows="10" />

            <div>
                <flux:label>{{ __('Categories') }}</flux:label>
                <flux:description>{{ __('Pick all that apply. Public filters use these tabs.') }}</flux:description>
                <div class="mt-2 flex flex-wrap gap-2">
                    @forelse ($this->categories as $cat)
                        <label class="flex cursor-pointer items-center gap-2 rounded-full border border-zinc-300 px-3 py-1.5 text-sm transition hover:border-emerald-500 dark:border-zinc-600 has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-500/10">
                            <input type="checkbox" wire:model="selectedCategories" value="{{ $cat->id }}" class="accent-emerald-500" />
                            <span class="size-2 rounded-full" style="background:{{ $cat->color ?? '#5eead4' }}"></span>
                            <span class="text-zinc-700 dark:text-zinc-300">{{ $cat->name }}</span>
                        </label>
                    @empty
                        <flux:text size="sm" class="text-zinc-500">
                            {{ __('No categories yet.') }} <flux:link :href="route('admin.projects.categories')" wire:navigate>{{ __('Create one') }} →</flux:link>
                        </flux:text>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- DETAILS --}}
        <div @class(['space-y-5', 'hidden' => $activeTab !== 'details'])>
            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="client"   label="{{ __('Client / company') }}" placeholder="Remetric Health (USA)" />
                <flux:input wire:model="role"     label="{{ __('Your role') }}"        placeholder="Senior Backend Developer" />
                <flux:input wire:model="industry" label="{{ __('Industry') }}"         placeholder="Healthcare" />
                <flux:input wire:model="location" label="{{ __('Location / market') }}" placeholder="USA" />
            </div>
            <div class="grid gap-4 md:grid-cols-3">
                <flux:input type="date" wire:model="started_at"   label="{{ __('Started') }}" />
                <flux:input type="date" wire:model="completed_at" label="{{ __('Completed') }}" />
                <div class="flex items-end">
                    <flux:switch wire:model="is_ongoing" label="{{ __('Ongoing project') }}" />
                </div>
            </div>
        </div>

        {{-- TECH & FEATURES --}}
        <div @class(['space-y-6', 'hidden' => $activeTab !== 'tech'])>
            {{-- Tech stack --}}
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="mb-3 flex items-center justify-between">
                    <div>
                        <flux:heading size="sm">{{ __('Tech stack') }}</flux:heading>
                        <flux:text size="sm" class="text-zinc-500">{{ __('One per row.') }}</flux:text>
                    </div>
                    <flux:button size="sm" wire:click="addTech" icon="plus">{{ __('Add') }}</flux:button>
                </div>
                <div class="space-y-2">
                    @foreach ($tech_stack as $i => $t)
                        <div class="flex items-center gap-2" wire:key="tech-{{ $i }}">
                            <flux:input wire:model="tech_stack.{{ $i }}" class="flex-1" placeholder="Laravel" />
                            <flux:button size="sm" variant="danger" wire:click="removeTech({{ $i }})" icon="trash" />
                        </div>
                    @endforeach
                    @if (empty($tech_stack))
                        <flux:text size="sm" class="text-zinc-500">{{ __('No tech yet.') }}</flux:text>
                    @endif
                </div>
            </div>

            {{-- Key features --}}
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="mb-3 flex items-center justify-between">
                    <div>
                        <flux:heading size="sm">{{ __('Key features') }}</flux:heading>
                        <flux:text size="sm" class="text-zinc-500">{{ __('Bullet points shown in the case-study modal.') }}</flux:text>
                    </div>
                    <flux:button size="sm" wire:click="addFeature" icon="plus">{{ __('Add') }}</flux:button>
                </div>
                <div class="space-y-2">
                    @foreach ($key_features as $i => $f)
                        <div class="flex items-start gap-2" wire:key="feature-{{ $i }}">
                            <flux:textarea wire:model="key_features.{{ $i }}" class="flex-1" rows="2" />
                            <flux:button size="sm" variant="danger" wire:click="removeFeature({{ $i }})" icon="trash" />
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Challenges --}}
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="mb-3 flex items-center justify-between">
                    <div>
                        <flux:heading size="sm">{{ __('Challenges solved') }}</flux:heading>
                        <flux:text size="sm" class="text-zinc-500">{{ __('Optional. Sells the difficulty of the work.') }}</flux:text>
                    </div>
                    <flux:button size="sm" wire:click="addChallenge" icon="plus">{{ __('Add') }}</flux:button>
                </div>
                <div class="space-y-2">
                    @foreach ($challenges as $i => $c)
                        <div class="flex items-start gap-2" wire:key="challenge-{{ $i }}">
                            <flux:textarea wire:model="challenges.{{ $i }}" class="flex-1" rows="2" />
                            <flux:button size="sm" variant="danger" wire:click="removeChallenge({{ $i }})" icon="trash" />
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- LINKS --}}
        <div @class(['space-y-5', 'hidden' => $activeTab !== 'links'])>
            <flux:input type="url" wire:model="live_url"        label="{{ __('Live site URL') }}"        placeholder="https://example.com" />
            <flux:input type="url" wire:model="repo_url"        label="{{ __('Repository URL') }}"      placeholder="https://github.com/..." />
            <flux:input type="url" wire:model="case_study_url"  label="{{ __('External case study URL') }}" placeholder="https://medium.com/..." />
            <flux:textarea wire:model="demo_credentials" label="{{ __('Demo credentials') }}" description="{{ __('Optional. Shown on the project card / modal so visitors can log in to a demo.') }}" rows="3" placeholder="admin@example.com / password" />
        </div>

        {{-- SAAS / SALE --}}
        <div @class(['space-y-6', 'hidden' => $activeTab !== 'commerce'])>
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading size="sm" class="mb-3">{{ __('SaaS product') }}</flux:heading>
                <flux:switch wire:model.live="is_saas" label="{{ __('This project is offered as a SaaS') }}" />
                @if ($is_saas)
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <flux:input type="url" wire:model="saas_url" label="{{ __('SaaS product URL') }}" placeholder="https://app.example.com" />
                        <flux:input wire:model="saas_pricing" label="{{ __('Pricing label') }}" placeholder="From $19/mo" />
                    </div>
                @endif
            </div>

            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading size="sm" class="mb-3">{{ __('For sale') }}</flux:heading>
                <flux:switch wire:model.live="is_for_sale" label="{{ __('I am selling this project / source') }}" />
                @if ($is_for_sale)
                    <div class="mt-4 grid gap-4 md:grid-cols-3">
                        <flux:input type="number" step="0.01" wire:model="selling_price" label="{{ __('Price') }}" placeholder="2999.00" />
                        <flux:select wire:model="selling_currency" label="{{ __('Currency') }}">
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                            <option value="GBP">GBP</option>
                            <option value="PKR">PKR</option>
                            <option value="AED">AED</option>
                        </flux:select>
                    </div>
                @endif
            </div>
        </div>

        {{-- MEDIA --}}
        <div @class(['space-y-6', 'hidden' => $activeTab !== 'media'])>
            @if (! $project)
                <flux:callout icon="information-circle">{{ __('Save the project first, then upload images here.') }}</flux:callout>
            @else
                <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="mb-3 flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <flux:heading size="md">{{ __('Add images') }}</flux:heading>
                            <flux:text size="sm" class="text-zinc-500">{{ __('JPG, PNG, WebP, GIF. Pick multiple at once.') }}</flux:text>
                        </div>
                        @if (! empty($newImages))
                            <flux:button variant="primary" wire:click="uploadImages" wire:loading.attr="disabled" icon="cloud-arrow-up">
                                <span wire:loading.remove wire:target="uploadImages">{{ __('Upload') }} ({{ count($newImages) }})</span>
                                <span wire:loading wire:target="uploadImages">{{ __('Uploading…') }}</span>
                            </flux:button>
                        @endif
                    </div>

                    <flux:input type="file" wire:model="newImages" accept="image/*" multiple />
                    @error('newImages.*') <flux:error>{{ $message }}</flux:error> @enderror

                    @if (! empty($newImages))
                        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                            @foreach ($newImages as $i => $file)
                                <div class="relative overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700" wire:key="newimg-{{ $i }}">
                                    <img src="{{ $file->temporaryUrl() }}" class="aspect-video w-full object-cover" />
                                    <div class="absolute right-1 top-1"><flux:badge color="emerald" size="sm">{{ __('Pending') }}</flux:badge></div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if ($project->images->isEmpty())
                    <div class="rounded-xl border border-dashed border-zinc-300 p-12 text-center text-zinc-500 dark:border-zinc-700">
                        {{ __('No images yet. Add the first one above.') }}
                    </div>
                @else
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($project->images as $img)
                            <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900" wire:key="img-{{ $img->id }}">
                                <div class="relative">
                                    <img src="{{ $img->url() }}" class="aspect-video w-full object-cover" />
                                    @if ($img->is_primary)
                                        <div class="absolute left-2 top-2"><flux:badge color="emerald" size="sm">{{ __('Primary') }}</flux:badge></div>
                                    @endif
                                    <div class="absolute right-2 top-2"><flux:badge color="zinc" size="sm">#{{ $img->sort_order }}</flux:badge></div>
                                </div>

                                <div class="space-y-3 p-3">
                                    <flux:input
                                        size="sm"
                                        value="{{ $img->alt }}"
                                        wire:change="updateImageAlt({{ $img->id }}, $event.target.value)"
                                        placeholder="Alt text"
                                    />
                                    <div class="flex items-center justify-between gap-1">
                                        <div class="flex gap-1">
                                            <flux:button size="sm" variant="ghost" wire:click="moveImage({{ $img->id }}, 'up')" icon="arrow-up" />
                                            <flux:button size="sm" variant="ghost" wire:click="moveImage({{ $img->id }}, 'down')" icon="arrow-down" />
                                        </div>
                                        <div class="flex gap-1">
                                            @if (! $img->is_primary)
                                                <flux:button size="sm" variant="ghost" wire:click="setPrimary({{ $img->id }})" icon="star" :title="__('Set primary')" />
                                            @endif
                                            <flux:button size="sm" variant="danger" wire:click="deleteImage({{ $img->id }})" icon="trash" wire:confirm="{{ __('Delete this image?') }}" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>

        {{-- STATUS / SEO --}}
        <div @class(['space-y-6', 'hidden' => $activeTab !== 'status'])>
            <div class="grid gap-4 md:grid-cols-3">
                <flux:select wire:model="status" label="{{ __('Status') }}">
                    <option value="completed">{{ __('Completed') }}</option>
                    <option value="in_progress">{{ __('In progress') }}</option>
                    <option value="planned">{{ __('Planned') }}</option>
                </flux:select>
                <flux:input type="number" wire:model="sort_order" label="{{ __('Sort order') }}" min="0" />
                <div class="flex flex-col gap-3 pt-6">
                    <flux:switch wire:model="is_published" label="{{ __('Published (visible on site)') }}" />
                    <flux:switch wire:model="is_featured"  label="{{ __('Featured') }}" />
                </div>
            </div>

            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading size="sm" class="mb-3">{{ __('SEO') }}</flux:heading>
                <div class="space-y-3">
                    <flux:input wire:model="seo_title" label="{{ __('SEO title') }}" />
                    <flux:textarea wire:model="seo_description" label="{{ __('SEO description') }}" rows="3" />
                </div>
            </div>
        </div>
    </div>

    {{-- Sticky save --}}
    <div class="sticky bottom-4 mt-4 flex justify-end">
        <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled" icon="check" class="shadow-lg">
            <span wire:loading.remove wire:target="save">{{ __('Save project') }}</span>
            <span wire:loading wire:target="save">{{ __('Saving…') }}</span>
        </flux:button>
    </div>
</div>
