<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Platform Hosts
    |--------------------------------------------------------------------------
    |
    | Hostnames that serve the platform itself (login, tenant dashboards,
    | the super-admin panel) rather than a tenant's public portfolio site.
    | Any other host must resolve to a verified tenant Domain or the request
    | is rejected — see App\Http\Middleware\ResolveTenantDomain.
    |
    */

    'platform_hosts' => array_filter(array_map(
        'trim',
        explode(',', (string) env('PLATFORM_HOSTS', (string) (parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'localhost')))
    )),

];
