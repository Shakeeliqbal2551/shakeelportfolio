<?php

namespace App\Models;

use App\Models\Concerns\HasResolvableFileUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Testimonial extends Model
{
    use HasFactory, HasResolvableFileUrl;

    protected $fillable = [
        'portfolio_id',
        'name',
        'role_company',
        'quote',
        'avatar_path',
        'sort_order',
    ];

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return static::resolveFileUrl($this->avatar_path);
    }
}
