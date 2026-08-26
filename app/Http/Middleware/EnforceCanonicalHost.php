<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceCanonicalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->isProduction()) {
            return $next($request);
        }

        // Requests already resolved to a verified tenant domain (see
        // ResolveTenantDomain, which runs first) are canonical as-is —
        // only the platform's own host(s) get scheme/host enforcement.
        if (! in_array($request->getHost(), config('portfolio.platform_hosts'), true)) {
            return $next($request);
        }

        $canonical = parse_url((string) config('app.url'));
        $canonicalScheme = $canonical['scheme'] ?? null;
        $canonicalHost = $canonical['host'] ?? null;

        if (! $canonicalScheme || ! $canonicalHost) {
            return $next($request);
        }

        if ($request->getScheme() !== $canonicalScheme || $request->getHost() !== $canonicalHost) {
            $redirectUrl = $canonicalScheme.'://'.$canonicalHost.$request->getRequestUri();

            return redirect()->to($redirectUrl, 301);
        }

        return $next($request);
    }
}
