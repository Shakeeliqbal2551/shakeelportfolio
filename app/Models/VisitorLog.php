<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPortfolio;
use Illuminate\Database\Eloquent\Model;

class VisitorLog extends Model
{
    use BelongsToPortfolio;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'visit_time'        => 'datetime',
            'is_repeat_visitor' => 'boolean',
        ];
    }
}
