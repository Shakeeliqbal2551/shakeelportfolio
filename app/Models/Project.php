<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPortfolio;
use App\Models\Concerns\HasMediaUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use BelongsToPortfolio, HasMediaUrl, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tech_stack'      => 'array',
            'key_features'    => 'array',
            'challenges'      => 'array',
            'started_at'      => 'date',
            'completed_at'    => 'date',
            'is_ongoing'      => 'boolean',
            'is_saas'         => 'boolean',
            'is_for_sale'     => 'boolean',
            'is_featured'     => 'boolean',
            'is_published'    => 'boolean',
            'selling_price'   => 'decimal:2',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ProjectCategory::class, 'project_project_category');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProjectImage::class)->orderBy('sort_order');
    }

    public function primaryImage()
    {
        return $this->hasOne(ProjectImage::class)->where('is_primary', true);
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_published', true);
    }

    public function scopeFeatured(Builder $q): Builder
    {
        return $q->where('is_featured', true);
    }

    public function primaryImageUrl(): ?string
    {
        $img = $this->primaryImage ?? $this->images->first();

        return $img ? $this->mediaUrl($img->path) : null;
    }
}
