<?php

namespace App\Http\Middleware;

use App\Models\Domain;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        if (in_array($host, config('portfolio.platform_hosts'), true)) {
            return $next($request);
        }

        $domain = Domain::where('host', $host)
            ->where('verification_status', 'verified')
            ->with('portfolio')
            ->first();

        if (! $domain || ! $domain->portfolio) {
            return response()->view('errors.domain-not-found', [], 404);
        }

        $request->attributes->set('resolvedPortfolio', $domain->portfolio);

        return $next($request);
    }
}
