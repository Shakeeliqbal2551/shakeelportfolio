<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPortfolio;
use App\Models\Concerns\HasMediaUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use BelongsToPortfolio, HasMediaUrl, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_active'         => 'boolean',
            'is_featured'       => 'boolean',
            'starting_price'    => 'decimal:2',
            'included_features' => 'array',
        ];
    }

    public function iconUrl(): ?string
    {
        return $this->mediaUrl($this->icon_path);
    }
}
