<?php

namespace App\Models;

use App\Models\Concerns\HasResolvableFileUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    use HasFactory, HasResolvableFileUrl;

    protected $fillable = [
        'portfolio_id',
        'title',
        'slug',
        'excerpt',
        'body',
        'featured_image_path',
        'meta_title',
        'meta_description',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function getFeaturedImageUrlAttribute(): ?string
    {
        return static::resolveFileUrl($this->featured_image_path);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('published_at', '<=', now());
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->lte(now());
    }
}
