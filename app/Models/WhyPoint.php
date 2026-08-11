<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPortfolio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WhyPoint extends Model
{
    use BelongsToPortfolio, SoftDeletes;

    protected $fillable = [
        'portfolio_id', 'label', 'title', 'description', 'icon', 'sort_order', 'is_visible',
    ];

    protected function casts(): array
    {
        return ['is_visible' => 'boolean'];
    }
}
