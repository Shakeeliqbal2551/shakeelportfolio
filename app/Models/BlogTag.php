<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPortfolio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlogTag extends Model
{
    use BelongsToPortfolio, SoftDeletes;

    protected $fillable = ['portfolio_id', 'name', 'slug'];

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'blog_post_tag');
    }
}
