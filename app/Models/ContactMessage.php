<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactMessage extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'portfolio_id',
        'name',
        'email',
        'phone',
        'message',
        'ip_address',
        'country',
        'city',
        'region',
        'latitude',
        'longitude',
        'user_agent',
        'browser',
        'os',
        'device_type',
        'submission_time',
    ];

    protected function casts(): array
    {
        return [
            'submission_time' => 'datetime',
        ];
    }

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }
}
