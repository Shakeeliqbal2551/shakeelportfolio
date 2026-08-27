<?php

namespace App\Models;

use App\Enums\ProjectRole;
use App\Models\Concerns\HasResolvableFileUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Project extends Model
{
    use HasFactory, HasResolvableFileUrl;

    protected $fillable = [
        'portfolio_id',
        'title',
        'slug',
        'description',
        'details',
        'image_path',
        'image_alt',
        'tags',
        'external_link',
        'featured',
        'sort_order',
        'role',
        'company_name',
        'your_title',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'featured' => 'boolean',
            'role' => ProjectRole::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Project $project) {
            if ($project->slug) {
                return;
            }

            $base = Str::slug($project->title);
            $slug = $base;
            $suffix = 2;

            while (static::where('portfolio_id', $project->portfolio_id)->where('slug', $slug)->exists()) {
                $slug = $base.'-'.$suffix;
                $suffix++;
            }

            $project->slug = $slug;
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        return static::resolveFileUrl($this->image_path);
    }

    public function isVenture(): bool
    {
        return $this->role !== ProjectRole::Client;
    }

    /**
     * Human-readable label built from tags (e.g. "SaaS & Healthcare") for
     * use in case-study page titles/headings — keeps the SEO title tied to
     * how the project is actually categorized rather than duplicating it.
     */
    public function getCategoryLabelAttribute(): ?string
    {
        $tags = collect($this->tags ?? [])->map(fn ($tag) => match ($tag) {
            'saas' => 'SaaS',
            'ecommerce' => 'eCommerce',
            default => ucfirst($tag),
        });

        return $tags->isEmpty() ? null : $tags->join(' & ');
    }

    /**
     * SEO title for the dedicated case-study page. Prefers a "management
     * system" / "platform" framing pulled from the project's own description
     * when it already uses that language (most seeded projects do), since
     * that phrasing is what buyers actually search for — falling back to a
     * tag-based category label otherwise.
     */
    public function getSeoTitleAttribute(): string
    {
        $subject = $this->descriptionSubject();

        return $subject
            ? "{$this->title} — {$subject} Development Case Study"
            : "{$this->title} — Case Study";
    }

    /**
     * Pulls a short, keyword-relevant subject phrase (e.g. "HR Management
     * System", "Multi-Vendor eCommerce Marketplace") out of the project's
     * description, falling back to the tag-based category label.
     */
    private function descriptionSubject(): ?string
    {
        $patterns = [
            '/\b((?:[A-Za-z-]+\s){0,3}management system)\b/i',
            '/\b((?:[A-Za-z-]+\s){0,3}management platform)\b/i',
            '/\b((?:[A-Za-z-]+\s){0,3}marketplace)\b/i',
            '/\b((?:[A-Za-z-]+\s){0,3}platform)\b/i',
            '/\b((?:[A-Za-z-]+\s){0,3}system)\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, (string) $this->description, $matches)) {
                $phrase = ucwords(trim($matches[1]));

                return preg_replace('/\bE[Cc]ommerce\b/', 'eCommerce', $phrase);
            }
        }

        return $this->category_label;
    }

    public function getSeoDescriptionAttribute(): string
    {
        return Str::limit($this->description, 155, '');
    }

    /**
     * Convert the plain-text `details` field into simple HTML: blank-line
     * separated paragraphs, with "- " prefixed lines inside a paragraph
     * rendered as a <ul> bullet list. Falls back to `description` if
     * `details` is empty. This intentionally does not implement full
     * markdown — it only supports the paragraph/bullet structure the
     * seeder and dashboard editor actually produce.
     */
    public function getDetailsHtmlAttribute(): ?string
    {
        $source = $this->details ?: $this->description;

        if (! $source) {
            return null;
        }

        $paragraphs = preg_split('/\n{2,}/', trim($source));
        $html = '';

        foreach ($paragraphs as $paragraph) {
            $lines = preg_split('/\n/', trim($paragraph));
            $bulletLines = [];
            $textLines = [];

            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                if (str_starts_with($line, '- ')) {
                    $bulletLines[] = e(substr($line, 2));
                } else {
                    $textLines[] = e($line);
                }
            }

            if ($textLines) {
                $html .= '<p>'.implode('<br>', $textLines).'</p>';
            }

            if ($bulletLines) {
                $html .= '<ul>'.collect($bulletLines)->map(fn ($item) => "<li>{$item}</li>")->implode('').'</ul>';
            }
        }

        return $html;
    }
}
