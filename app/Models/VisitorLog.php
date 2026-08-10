<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitorLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'portfolio_id',
        'ip_address',
        'country',
        'city',
        'region',
        'latitude',
        'longitude',
        'page_visited',
        'user_agent',
        'browser',
        'os',
        'device_type',
        'screen_resolution',
        'is_repeat_visitor',
        'visit_time',
        'duration_seconds',
        'session_token',
    ];

    protected function casts(): array
    {
        return [
            'is_repeat_visitor' => 'boolean',
            'visit_time' => 'datetime',
            'duration_seconds' => 'integer',
        ];
    }

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }
}
