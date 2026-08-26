<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Domain extends Model
{
    use HasFactory;

    protected $fillable = [
        'portfolio_id',
        'host',
        'is_primary',
        'verification_status',
        'verification_token',
        'verified_at',
        'ssl_status',
        'ssl_issued_at',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'verified_at' => 'datetime',
            'ssl_issued_at' => 'datetime',
        ];
    }

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function isVerified(): bool
    {
        return $this->verification_status === 'verified';
    }

    /**
     * Mark this domain as the primary domain for its portfolio, unsetting
     * any sibling domain currently marked primary.
     */
    public function markPrimary(): void
    {
        DB::transaction(function () {
            static::where('portfolio_id', $this->portfolio_id)
                ->where('id', '!=', $this->id)
                ->update(['is_primary' => false]);

            $this->update(['is_primary' => true]);
        });
    }
}
