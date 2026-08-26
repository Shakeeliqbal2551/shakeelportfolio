<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Portfolio extends Model
{
    use HasFactory;

    public const DEFAULT_SLUG = 'shakeel-iqbal-cheema';

    protected $fillable = [
        'user_id',
        'slug',
        'theme',
        'site_title',
        'meta_description',
        'blog_meta_description',
        'hero_badge_text',
        'hero_subtitle',
        'hero_title',
        'hero_title_accent',
        'hero_description',
        'hero_cta_primary_label',
        'hero_cta_primary_href',
        'hero_cta_secondary_label',
        'hero_cta_secondary_href',
        'hero_reassurance_items',
        'hero_stats',
        'trust_label',
        'trust_flags',
        'contact_email',
        'whatsapp_number',
    ];

    protected function casts(): array
    {
        return [
            'hero_reassurance_items' => 'array',
            'hero_stats' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * The default portfolio shown at the site root and used as the fallback
     * scope for the short-form /blog routes.
     */
    public static function default(): self
    {
        return static::where('slug', self::DEFAULT_SLUG)->firstOrFail();
    }

    public function isDefault(): bool
    {
        return $this->slug === self::DEFAULT_SLUG;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function about(): HasOne
    {
        return $this->hasOne(AboutSection::class);
    }

    public function profileImages(): HasMany
    {
        return $this->hasMany(ProfileImage::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(Experience::class);
    }

    public function educations(): HasMany
    {
        return $this->hasMany(Education::class);
    }

    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function testimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function visitorLogs(): HasMany
    {
        return $this->hasMany(VisitorLog::class);
    }

    public function contactMessages(): HasMany
    {
        return $this->hasMany(ContactMessage::class);
    }
}
