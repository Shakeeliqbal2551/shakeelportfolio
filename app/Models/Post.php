<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPortfolio;
use App\Models\Concerns\HasMediaUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Post extends Model
{
    use BelongsToPortfolio, HasMediaUrl, SoftDeletes;

    public const STATUS_DRAFT     = 'draft';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED  = 'archived';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'gallery'         => 'array',
            'content_blocks'  => 'array',
            'published_at'    => 'datetime',
            'is_featured'     => 'boolean',
            'allow_comments'  => 'boolean',
            'views_count'     => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(BlogTag::class, 'blog_post_tag');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_PUBLISHED)
                 ->whereNotNull('published_at')
                 ->where('published_at', '<=', now());
    }

    public function scopeDraft(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_DRAFT);
    }

    public function featuredImageUrl(): ?string
    {
        return $this->mediaUrl($this->featured_image_path);
    }

    public function calculateReadingTime(): int
    {
        $words = str_word_count(strip_tags((string) $this->content));

        return max(1, (int) ceil($words / 200));
    }

    public function generateExcerpt(int $words = 32): string
    {
        return Str::words(strip_tags((string) $this->content), $words, '…');
    }
}
