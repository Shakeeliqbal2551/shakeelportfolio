<?php

use App\Enums\ProjectRole;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public int $portfolioId;

    public ?int $editingId = null;

    public string $title = '';
    public string $description = '';
    public ?string $details = null;
    public $image = null;
    public ?string $existingImagePath = null;
    public ?string $image_alt = null;
    public string $tags = '';
    public ?string $external_link = null;
    public bool $featured = false;
    public string $role = ProjectRole::Client->value;
    public ?string $company_name = null;
    public ?string $your_title = null;

    public ?int $deletingId = null;

    public function mount(): void
    {
        $portfolio = Auth::user()->portfolio;

        abort_if(! $portfolio, 404);

        $this->portfolioId = $portfolio->id;
    }

    public function getProjectsProperty()
    {
        return Project::where('portfolio_id', $this->portfolioId)
            ->orderBy('sort_order')
            ->get();
    }

    public function createNew(): void
    {
        $this->resetForm();
        $this->modal('project-form')->show();
    }

    public function edit(int $id): void
    {
        $project = Project::where('portfolio_id', $this->portfolioId)->findOrFail($id);

        $this->editingId = $project->id;
        $this->title = $project->title;
        $this->description = $project->description;
        $this->details = $project->details;
        $this->existingImagePath = $project->image_path;
        $this->image_alt = $project->image_alt;
        $this->tags = implode(', ', $project->tags ?? []);
        $this->external_link = $project->external_link;
        $this->featured = $project->featured;
        $this->role = $project->role->value;
        $this->company_name = $project->company_name;
        $this->your_title = $project->your_title;

        $this->modal('project-form')->show();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'details' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'image_alt' => 'nullable|string|max:255',
            'tags' => 'nullable|string',
            'external_link' => 'nullable|url|max:255',
            'featured' => 'boolean',
            'role' => ['required', 'string', Rule::enum(ProjectRole::class)],
            'company_name' => 'nullable|string|max:255',
            'your_title' => 'nullable|string|max:255',
        ]);

        $validated['tags'] = collect(explode(',', $this->tags))
            ->map(fn ($tag) => trim($tag))
            ->filter()
            ->values()
            ->all();

        unset($validated['image']);

        if ($this->image) {
            if ($this->existingImagePath) {
                Storage::disk('public')->delete($this->existingImagePath);
            }

            $validated['image_path'] = $this->image->store("portfolios/{$this->portfolioId}/projects", 'public');
        }

        if ($this->editingId) {
            $project = Project::where('portfolio_id', $this->portfolioId)->findOrFail($this->editingId);
            $project->update($validated);
        } else {
            $maxOrder = Project::where('portfolio_id', $this->portfolioId)->max('sort_order');
            $validated['portfolio_id'] = $this->portfolioId;
            $validated['sort_order'] = $maxOrder === null ? 0 : $maxOrder + 1;
            Project::create($validated);
        }

        $this->modal('project-form')->close();
        $this->resetForm();
        $this->dispatch('project-saved');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->modal('project-delete')->show();
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $project = Project::where('portfolio_id', $this->portfolioId)->where('id', $this->deletingId)->first();

            if ($project) {
                if ($project->image_path) {
                    Storage::disk('public')->delete($project->image_path);
                }

                $project->delete();
            }
        }

        $this->deletingId = null;
        $this->modal('project-delete')->close();
        $this->dispatch('project-saved');
    }

    public function moveUp(int $id): void
    {
        $this->swap($id, 'up');
    }

    public function moveDown(int $id): void
    {
        $this->swap($id, 'down');
    }

    protected function swap(int $id, string $direction): void
    {
        $project = Project::where('portfolio_id', $this->portfolioId)->findOrFail($id);

        $sibling = Project::where('portfolio_id', $this->portfolioId)
            ->when($direction === 'up', fn ($q) => $q->where('sort_order', '<', $project->sort_order)->orderBy('sort_order', 'desc'))
            ->when($direction === 'down', fn ($q) => $q->where('sort_order', '>', $project->sort_order)->orderBy('sort_order', 'asc'))
            ->first();

        if (! $sibling) {
            return;
        }

        DB::transaction(function () use ($project, $sibling) {
            $originalOrder = $project->sort_order;
            $project->update(['sort_order' => $sibling->sort_order]);
            $sibling->update(['sort_order' => $originalOrder]);
        });
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'title', 'description', 'details', 'image', 'existingImagePath', 'image_alt', 'tags', 'external_link', 'featured', 'company_name', 'your_title']);
        $this->role = ProjectRole::Client->value;
        $this->resetValidation();
    }
}; ?>

<section class="w-full">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Projects') }}</flux:heading>
            <flux:subheading>{{ __('Manage your portfolio projects') }}</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="createNew">
            {{ __('Add New') }}
        </flux:button>
    </div>

    <div class="my-6 flex items-center gap-4">
        <x-action-message on="project-saved">{{ __('Saved.') }}</x-action-message>
    </div>

    <div class="space-y-3">
        @forelse ($this->projects as $index => $project)
            <div class="flex items-center justify-between gap-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="flex items-center gap-3">
                    @if ($project->image_path)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($project->image_path) }}" alt="{{ $project->title }}" class="h-12 w-12 rounded-lg object-cover">
                    @endif
                    <div>
                        <div class="flex items-center gap-2">
                            <flux:heading>{{ $project->title }}</flux:heading>
                            @if ($project->featured)
                                <flux:badge size="sm" color="amber">{{ __('Featured') }}</flux:badge>
                            @endif
                        </div>
                        <flux:text class="text-zinc-500">{{ \Illuminate\Support\Str::limit($project->description, 80) }}</flux:text>
                    </div>
                </div>

                <div class="flex items-center gap-1">
                    <flux:button size="sm" variant="subtle" icon="chevron-up" wire:click="moveUp({{ $project->id }})" :disabled="$index === 0" />
                    <flux:button size="sm" variant="subtle" icon="chevron-down" wire:click="moveDown({{ $project->id }})" :disabled="$index === $this->projects->count() - 1" />
                    <flux:button size="sm" variant="subtle" icon="pencil-square" wire:click="edit({{ $project->id }})" />
                    <flux:button size="sm" variant="subtle" icon="trash" wire:click="confirmDelete({{ $project->id }})" />
                </div>
            </div>
        @empty
            <flux:text class="text-zinc-500">{{ __('No projects yet.') }}</flux:text>
        @endforelse
    </div>

    <flux:modal name="project-form" class="max-w-2xl" @close="resetForm">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">{{ $editingId ? __('Edit Project') : __('Add Project') }}</flux:heading>

            <flux:input wire:model="title" :label="__('Title')" type="text" required />
            <flux:textarea wire:model="description" :label="__('Description')" rows="3" required />
            <flux:textarea wire:model="details" :label="__('Details')" rows="6" />

            <div>
                <flux:input wire:model="image" :label="__('Image')" type="file" accept="image/*" />
                @if ($existingImagePath)
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($existingImagePath) }}" alt="{{ __('Current image') }}" class="mt-2 h-24 w-24 rounded-lg object-cover">
                @endif
            </div>

            <flux:input wire:model="image_alt" :label="__('Image Alt Text (for SEO)')" type="text" placeholder="{{ $title ? $title.' — screenshot' : 'e.g. Acme Dashboard — screenshot' }}" description="{{ __('Optional — describe the image for search engines and screen readers. Falls back to the project title if left blank.') }}" />

            <flux:input wire:model="tags" :label="__('Tags')" type="text" placeholder="Laravel, Vue, Tailwind" description="{{ __('Comma-separated') }}" />
            <flux:input wire:model="external_link" :label="__('External Link')" type="url" />
            <flux:checkbox wire:model="featured" :label="__('Featured')" />

            <flux:select wire:model="role" :label="__('Role')" description="{{ __('How you were involved in this project') }}">
                @foreach (\App\Enums\ProjectRole::cases() as $roleOption)
                    <flux:select.option value="{{ $roleOption->value }}">{{ $roleOption->label() }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="company_name" :label="__('Company / Venture Name')" type="text" description="{{ __('Shown alongside your role badge, e.g. the product or company name') }}" />
            <flux:input wire:model="your_title" :label="__('Your Title')" type="text" placeholder="Founder & Developer" />

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="project-delete" class="max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('Delete project?') }}</flux:heading>
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
