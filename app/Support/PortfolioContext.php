<?php

namespace App\Support;

use App\Models\Portfolio;
use Illuminate\Support\Facades\Cache;

/**
 * Resolves the "current" portfolio for a request. In single-tenant mode this
 * is just the first active portfolio (e.g. Shakeel's). When the SaaS goes
 * multi-tenant, swap the resolver here (subdomain / route param / auth user)
 * — every consumer in the app continues to call PortfolioContext::current().
 */
class PortfolioContext
{
    protected static ?Portfolio $current = null;

    public static function current(): ?Portfolio
    {
        if (static::$current) {
            return static::$current;
        }

        return static::$current = Cache::remember(
            'portfolio.context.primary',
            now()->addMinutes(10),
            fn () => Portfolio::where('is_active', true)
                ->orderByDesc('is_primary')
                ->orderBy('id')
                ->with('settings')
                ->first()
        );
    }

    public static function set(Portfolio $portfolio): void
    {
        static::$current = $portfolio;
    }

    public static function clear(): void
    {
        static::$current = null;
        Cache::forget('portfolio.context.primary');
    }

    public static function id(): ?int
    {
        return static::current()?->id;
    }
}
