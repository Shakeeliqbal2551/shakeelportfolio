<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPortfolio;
use App\Models\Concerns\HasMediaUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Experience extends Model
{
    use BelongsToPortfolio, HasMediaUrl, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date'   => 'date',
            'is_current' => 'boolean',
            'is_visible' => 'boolean',
            'highlights' => 'array',
        ];
    }

    public function logoUrl(): ?string
    {
        return $this->mediaUrl($this->logo_path);
    }
}
