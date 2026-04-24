<?php

namespace App\Models;

use App\Models\Concerns\HasMediaUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectImage extends Model
{
    use HasMediaUrl, SoftDeletes;

    protected $fillable = [
        'project_id', 'path', 'alt', 'caption', 'width', 'height', 'is_primary', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function url(): ?string
    {
        return $this->mediaUrl($this->path);
    }
}
