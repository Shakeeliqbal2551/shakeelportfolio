<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPortfolio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Skill extends Model
{
    use BelongsToPortfolio, SoftDeletes;

    protected $fillable = [
        'portfolio_id', 'skill_category_id', 'name', 'proficiency',
        'years_experience', 'icon', 'is_featured', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active'   => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(SkillCategory::class, 'skill_category_id');
    }
}
