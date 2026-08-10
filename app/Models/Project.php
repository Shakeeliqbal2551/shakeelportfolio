<?php

namespace App\Models;

use App\Enums\ProjectRole;
use App\Models\Concerns\HasResolvableFileUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Project extends Model
{
    use HasFactory, HasResolvableFileUrl;

    protected $fillable = [
        'portfolio_id',
        'title',
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
