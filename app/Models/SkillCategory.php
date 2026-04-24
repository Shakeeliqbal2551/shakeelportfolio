<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPortfolio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SkillCategory extends Model
{
    use BelongsToPortfolio, SoftDeletes;

    protected $fillable = [
        'portfolio_id', 'name', 'slug', 'icon', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class)->orderBy('sort_order');
    }
}
