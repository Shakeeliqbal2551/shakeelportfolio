<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPortfolio;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactMessage extends Model
{
    use BelongsToPortfolio, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'submission_time' => 'datetime',
            'is_read'         => 'boolean',
            'is_archived'     => 'boolean',
        ];
    }

    public function scopeUnread(Builder $q): Builder
    {
        return $q->where('is_read', false);
    }
}
