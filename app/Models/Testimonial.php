<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPortfolio;
use App\Models\Concerns\HasMediaUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Testimonial extends Model
{
    use BelongsToPortfolio, HasMediaUrl, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_visible'  => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function avatarUrl(): ?string
    {
        return $this->mediaUrl($this->avatar_path);
    }
}
