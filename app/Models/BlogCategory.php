<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPortfolio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlogCategory extends Model
{
    use BelongsToPortfolio, SoftDeletes;

    protected $fillable = [
        'portfolio_id', 'name', 'slug', 'description', 'color', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
