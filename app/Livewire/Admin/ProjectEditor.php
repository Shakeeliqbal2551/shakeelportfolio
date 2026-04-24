<?php

namespace App\Livewire\Admin;

use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\ProjectImage;
use App\Services\MediaService;
use App\Support\PortfolioContext;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProjectEditor extends Component
{
    use WithFileUploads;

    public ?Project $project = null;

    public string $activeTab = 'overview';

    // Identity
    public string $title = '';
    public string $slug = '';
    public string $tagline = '';
    public string $summary = '';
    public string $description = '';

    // Meta
    public string $client = '';
    public string $role = '';
    public string $industry = '';
    public string $location = '';
    public ?string $started_at = null;
    public ?string $completed_at = null;
    public bool $is_ongoing = false;

    // Tech
    public array $tech_stack = [];
    public array $key_features = [];
    public array $challenges = [];

    // Links
    public string $live_url = '';
    public string $repo_url = '';
    public string $case_study_url = '';
    public string $demo_credentials = '';

    // SaaS / Sale
    public bool $is_saas = false;
    public string $saas_url = '';
    public string $saas_pricing = '';
    public bool $is_for_sale = false;
    public ?string $selling_price = null;
    public string $selling_currency = 'USD';

    // Status
    public string $status = 'completed';
    public bool $is_featured = false;
    public bool $is_published = true;
    public int $sort_order = 0;

    // SEO
    public string $seo_title = '';
    public string $seo_description = '';

    // Categories
    public array $selectedCategories = [];

    // Image upload buffer
    public array $newImages = [];

    public function mount(?int $projectId = null): void
    {
        $portfolio = PortfolioContext::current();
        abort_unless($portfolio, 404);

        if ($projectId) {
            $this->project = Project::with('categories', 'images')
                ->where('portfolio_id', $portfolio->id)
                ->findOrFail($projectId);

            $this->loadFromProject();
        } else {
            $this->sort_order = (int) (($portfolio->projects()->max('sort_order') ?? 0) + 10);
        }
    }

    protected function loadFromProject(): void
    {
        $p = $this->project;

        $this->title             = $p->title ?? '';
        $this->slug              = $p->slug ?? '';
        $this->tagline           = $p->tagline ?? '';
        $this->summary           = $p->summary ?? '';
        $this->description       = $p->description ?? '';
        $this->client            = $p->client ?? '';
        $this->role              = $p->role ?? '';
        $this->industry          = $p->industry ?? '';
        $this->location          = $p->location ?? '';
        $this->started_at        = $p->started_at?->format('Y-m-d');
        $this->completed_at      = $p->completed_at?->format('Y-m-d');
        $this->is_ongoing        = (bool) $p->is_ongoing;
        $this->tech_stack        = $p->tech_stack ?? [];
        $this->key_features      = $p->key_features ?? [];
        $this->challenges        = $p->challenges ?? [];
        $this->live_url          = $p->live_url ?? '';
        $this->repo_url          = $p->repo_url ?? '';
        $this->case_study_url    = $p->case_study_url ?? '';
        $this->demo_credentials  = $p->demo_credentials ?? '';
        $this->is_saas           = (bool) $p->is_saas;
        $this->saas_url          = $p->saas_url ?? '';
        $this->saas_pricing      = $p->saas_pricing ?? '';
        $this->is_for_sale       = (bool) $p->is_for_sale;
        $this->selling_price     = $p->selling_price ? (string) $p->selling_price : null;
        $this->selling_currency  = $p->selling_currency ?? 'USD';
        $this->status            = $p->status ?? 'completed';
        $this->is_featured       = (bool) $p->is_featured;
        $this->is_published      = (bool) $p->is_published;
        $this->sort_order        = (int) $p->sort_order;
        $this->seo_title         = $p->seo_title ?? '';
        $this->seo_description   = $p->seo_description ?? '';
        $this->selectedCategories = $p->categories->pluck('id')->all();
    }

    #[Computed]
    public function categories()
    {
        return PortfolioContext::current()
            ?->projectCategories()
            ->orderBy('sort_order')
            ->get() ?? collect();
    }

    public function rules(): array
    {
        $portfolio = PortfolioContext::current();
        $existingId = $this->project?->id;
        $maxKb = config('media.max_upload_kb', 8192);

        return [
            'title'            => ['required', 'string', 'max:200'],
            'slug'             => ['required', 'string', 'alpha_dash', 'max:200', "unique:projects,slug,{$existingId},id,portfolio_id,{$portfolio?->id}"],
            'tagline'          => ['nullable', 'string', 'max:200'],
            'summary'          => ['nullable', 'string', 'max:500'],
            'description'      => ['nullable', 'string'],
            'client'           => ['nullable', 'string', 'max:160'],
            'role'             => ['nullable', 'string', 'max:160'],
            'industry'         => ['nullable', 'string', 'max:160'],
            'location'         => ['nullable', 'string', 'max:160'],
            'started_at'       => ['nullable', 'date'],
            'completed_at'     => ['nullable', 'date', 'after_or_equal:started_at'],
            'is_ongoing'       => ['boolean'],
            'tech_stack'       => ['array'],
            'tech_stack.*'     => ['nullable', 'string', 'max:60'],
            'key_features'     => ['array'],
            'key_features.*'   => ['nullable', 'string', 'max:300'],
            'challenges'       => ['array'],
            'challenges.*'     => ['nullable', 'string', 'max:300'],
            'live_url'         => ['nullable', 'url', 'max:255'],
            'repo_url'         => ['nullable', 'url', 'max:255'],
            'case_study_url'   => ['nullable', 'url', 'max:255'],
            'demo_credentials' => ['nullable', 'string', 'max:1000'],
            'is_saas'          => ['boolean'],
            'saas_url'         => ['nullable', 'url', 'max:255'],
            'saas_pricing'     => ['nullable', 'string', 'max:120'],
            'is_for_sale'      => ['boolean'],
            'selling_price'    => ['nullable', 'numeric', 'min:0'],
            'selling_currency' => ['nullable', 'string', 'max:8'],
            'status'           => ['required', 'string', 'in:completed,in_progress,planned'],
            'is_featured'      => ['boolean'],
            'is_published'     => ['boolean'],
            'sort_order'       => ['integer', 'min:0'],
            'seo_title'        => ['nullable', 'string', 'max:255'],
            'seo_description'  => ['nullable', 'string', 'max:500'],
            'selectedCategories'   => ['array'],
            'selectedCategories.*' => ['integer', 'exists:project_categories,id'],
            'newImages'        => ['array', 'max:30'],
            'newImages.*'      => ['image', 'mimes:jpg,jpeg,png,webp,gif', "max:$maxKb"],
        ];
    }

    public function updatedTitle(string $value): void
    {
        if (! $this->project && empty($this->slug)) {
            $this->slug = Str::slug($value);
        }
    }

    // Repeater helpers
    public function addTech(): void           { $this->tech_stack[]   = ''; }
    public function removeTech(int $i): void  { unset($this->tech_stack[$i]);   $this->tech_stack   = array_values($this->tech_stack); }
    public function addFeature(): void        { $this->key_features[] = ''; }
    public function removeFeature(int $i): void{ unset($this->key_features[$i]); $this->key_features = array_values($this->key_features); }
    public function addChallenge(): void      { $this->challenges[]   = ''; }
    public function removeChallenge(int $i): void { unset($this->challenges[$i]); $this->challenges = array_values($this->challenges); }

    public function uploadImages(MediaService $media): void
    {
        $this->validate(['newImages.*' => $this->rules()['newImages.*']]);

        if (! $this->project) {
            // Save the project shell first so we have an id to attach images to.
            \Flux\Flux::toast(heading: __('Save first'), text: __('Save the project before uploading images.'), variant: 'warning');
            return;
        }

        $next = (int) (($this->project->images()->max('sort_order') ?? 0) + 10);
        $hasPrimary = $this->project->images()->where('is_primary', true)->exists();
        $count = count($this->newImages);

        foreach ($this->newImages as $i => $file) {
            $path = $media->store($file, 'project_images');

            ProjectImage::create([
                'project_id' => $this->project->id,
                'path'       => $path,
                'alt'        => $this->title,
                'sort_order' => $next,
                'is_primary' => ! $hasPrimary && $i === 0,
            ]);

            $next += 10;
            $hasPrimary = true;
        }

        $this->newImages = [];
        $this->project->load('images');

        \Flux\Flux::toast(heading: __('Uploaded'), text: $count.' '.($count === 1 ? __('image added.') : __('images added.')), variant: 'success');
    }

    public function deleteImage(int $imageId, MediaService $media): void
    {
        $img = ProjectImage::findOrFail($imageId);
        $media->delete($img->path);
        $img->delete();
        $this->project?->load('images');
    }

    public function setPrimary(int $imageId): void
    {
        $img = ProjectImage::findOrFail($imageId);
        ProjectImage::where('project_id', $img->project_id)->update(['is_primary' => false]);
        $img->update(['is_primary' => true]);
        $this->project?->load('images');
    }

    public function moveImage(int $imageId, string $direction): void
    {
        $img = ProjectImage::findOrFail($imageId);
        $op = $direction === 'up' ? '<' : '>';
        $order = $direction === 'up' ? 'desc' : 'asc';

        $neighbor = ProjectImage::where('project_id', $img->project_id)
            ->where('sort_order', $op, $img->sort_order)
            ->orderBy('sort_order', $order)
            ->first();

        if ($neighbor) {
            $tmp = $img->sort_order;
            $img->update(['sort_order' => $neighbor->sort_order]);
            $neighbor->update(['sort_order' => $tmp]);
        }

        $this->project?->load('images');
    }

    public function updateImageAlt(int $imageId, string $alt): void
    {
        ProjectImage::findOrFail($imageId)->update(['alt' => $alt]);
    }

    public function save()
    {
        $data = $this->validate();
        $portfolio = PortfolioContext::current();

        $payload = [
            'title'            => $data['title'],
            'slug'             => $data['slug'],
            'tagline'          => $data['tagline'] ?: null,
            'summary'          => $data['summary'] ?: null,
            'description'      => $data['description'] ?: null,
            'client'           => $data['client'] ?: null,
            'role'             => $data['role'] ?: null,
            'industry'         => $data['industry'] ?: null,
            'location'         => $data['location'] ?: null,
            'started_at'       => $data['started_at'] ?: null,
            'completed_at'     => $data['completed_at'] ?: null,
            'is_ongoing'       => $data['is_ongoing'],
            'tech_stack'       => array_values(array_filter($data['tech_stack'],   fn ($v) => trim((string) $v) !== '')),
            'key_features'     => array_values(array_filter($data['key_features'], fn ($v) => trim((string) $v) !== '')),
            'challenges'       => array_values(array_filter($data['challenges'],   fn ($v) => trim((string) $v) !== '')),
            'live_url'         => $data['live_url'] ?: null,
            'repo_url'         => $data['repo_url'] ?: null,
            'case_study_url'   => $data['case_study_url'] ?: null,
            'demo_credentials' => $data['demo_credentials'] ?: null,
            'is_saas'          => $data['is_saas'],
            'saas_url'         => $data['saas_url'] ?: null,
            'saas_pricing'     => $data['saas_pricing'] ?: null,
            'is_for_sale'      => $data['is_for_sale'],
            'selling_price'    => $data['selling_price'],
            'selling_currency' => $data['selling_currency'] ?: 'USD',
            'status'           => $data['status'],
            'is_featured'      => $data['is_featured'],
            'is_published'     => $data['is_published'],
            'sort_order'       => $data['sort_order'],
            'seo_title'        => $data['seo_title'] ?: null,
            'seo_description'  => $data['seo_description'] ?: null,
        ];

        if ($this->project) {
            $this->project->update($payload);
        } else {
            $this->project = Project::create($payload + ['portfolio_id' => $portfolio->id]);
        }

        $this->project->categories()->sync($data['selectedCategories'] ?? []);

        \Flux\Flux::toast(heading: __('Saved'), text: __('Project saved.'), variant: 'success');

        // If we just created, redirect to the edit URL so subsequent uploads work.
        if (! request()->routeIs('admin.projects.edit')) {
            return redirect()->route('admin.projects.edit', $this->project->id);
        }

        $this->project->load('categories', 'images');
    }

    public function render()
    {
        return view('livewire.admin.project-editor');
    }
}
