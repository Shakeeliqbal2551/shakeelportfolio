<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPortfolio;
use App\Models\Concerns\HasMediaUrl;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    use BelongsToPortfolio, HasMediaUrl;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'hero_reassurance' => 'array',
            'hero_flags'       => 'array',
            'theme_options'    => 'array',
        ];
    }

    public function resumeUrl(): ?string
    {
        return $this->mediaUrl($this->about_resume_path);
    }

    public function ogImageUrl(): ?string
    {
        return $this->mediaUrl($this->seo_og_image);
    }
}
