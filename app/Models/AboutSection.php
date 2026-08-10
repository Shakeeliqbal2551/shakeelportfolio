<?php

namespace App\Models;

use App\Models\Concerns\HasResolvableFileUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AboutSection extends Model
{
    use HasFactory, HasResolvableFileUrl;

    protected $fillable = [
        'portfolio_id',
        'heading',
        'title',
        'bio',
        'info_cards',
        'cta_heading',
        'cta_text',
        'resume_path',
        'profile_image_path',
        'alt_text',
    ];

    protected function casts(): array
    {
        return [
            'info_cards' => 'array',
        ];
    }

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function getResumeUrlAttribute(): ?string
    {
        return static::resolveFileUrl($this->resume_path);
    }

    public function getProfileImageUrlAttribute(): ?string
    {
        return static::resolveFileUrl($this->profile_image_path);
    }
}
