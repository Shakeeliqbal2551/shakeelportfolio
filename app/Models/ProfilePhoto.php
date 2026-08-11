<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPortfolio;
use App\Models\Concerns\HasMediaUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProfilePhoto extends Model
{
    use BelongsToPortfolio, HasMediaUrl, SoftDeletes;

    protected $fillable = [
        'portfolio_id', 'path', 'alt', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function url(): ?string
    {
        return $this->mediaUrl($this->path);
    }
}
