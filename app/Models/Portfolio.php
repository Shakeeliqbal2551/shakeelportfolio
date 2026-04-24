<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Portfolio extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'slug', 'display_name', 'headline', 'theme', 'is_active', 'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'is_primary' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function settings(): HasOne
    {
        return $this->hasOne(SiteSetting::class);
    }

    public function profilePhotos(): HasMany
    {
        return $this->hasMany(ProfilePhoto::class)->orderBy('sort_order');
    }

    public function projectCategories(): HasMany
    {
        return $this->hasMany(ProjectCategory::class)->orderBy('sort_order');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class)->orderBy('sort_order');
    }

    public function skillCategories(): HasMany
    {
        return $this->hasMany(SkillCategory::class)->orderBy('sort_order');
    }

    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class)->orderBy('sort_order');
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(Experience::class)->orderBy('sort_order');
    }

    public function educations(): HasMany
    {
        return $this->hasMany(Education::class)->orderBy('sort_order');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class)->orderBy('sort_order');
    }

    public function testimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class)->orderBy('sort_order');
    }

    public function whyPoints(): HasMany
    {
        return $this->hasMany(WhyPoint::class)->orderBy('sort_order');
    }

    public function blogCategories(): HasMany
    {
        return $this->hasMany(BlogCategory::class)->orderBy('sort_order');
    }

    public function blogTags(): HasMany
    {
        return $this->hasMany(BlogTag::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class)->latest('published_at');
    }
}
