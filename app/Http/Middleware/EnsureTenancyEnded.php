<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures tenancy is properly ended after each HTTP request.
 *
 * php artisan serve runs as a long-lived process where the Laravel
 * application container (and all singletons) persist between requests.
 * stancl/tenancy's Tenancy singleton keeps `initialized = true` across
 * requests unless explicitly ended. This causes the DB connection to
 * remain on the tenant database for subsequent requests, making the
 * DomainTenantResolver query against the wrong database and throw
 * TenantCouldNotBeIdentifiedOnDomainException.
 *
 * This middleware terminates tenancy at the end of every request to
 * ensure the central DB connection is always restored.
 */
class EnsureTenancyEnded
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }
    }
}
