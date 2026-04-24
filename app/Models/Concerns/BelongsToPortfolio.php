<?php

namespace App\Models\Concerns;

use App\Models\Portfolio;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Adds the standard portfolio_id relationship + scoping helpers.
 */
trait BelongsToPortfolio
{
    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function scopeForPortfolio(Builder $query, int|Portfolio $portfolio): Builder
    {
        return $query->where('portfolio_id', $portfolio instanceof Portfolio ? $portfolio->id : $portfolio);
    }
}
