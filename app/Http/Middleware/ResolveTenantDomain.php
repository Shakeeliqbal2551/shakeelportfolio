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

        // Treat an unregistered www hostname as an alias of a verified apex
        // domain. Exact matches above still win, allowing www to be configured
        // as an independent domain when desired.
        if (! $domain && str_starts_with($host, 'www.')) {
            $apexHost = substr($host, 4);
            $apexDomainExists = Domain::where('host', $apexHost)
                ->where('verification_status', 'verified')
                ->exists();

            if ($apexDomainExists) {
                $redirectUrl = $request->getScheme().'://'.$apexHost.$request->getRequestUri();

                return redirect()->to($redirectUrl, 301);
            }
        }

        if (! $domain || ! $domain->portfolio) {
            return response()->view('errors.domain-not-found', [], 404);
        }

        $request->attributes->set('resolvedPortfolio', $domain->portfolio);

        return $next($request);
    }
}
