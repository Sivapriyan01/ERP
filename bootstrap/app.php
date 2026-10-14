<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web:      __DIR__.'/../routes/web.php',
        api:      __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health:   '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectTo(function ($request) {
            $hostname = $request->getHost();
            $centralDomains = config('tenancy.central_domains');

            if (! in_array($hostname, $centralDomains)) {
                return route('tenant.login');
            }

            return route('superadmin.login');
        });

        // OWASP A05: Apply security headers on every HTTP response
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Ensure tenancy is ended after every request so that the Tenancy
        // singleton is reset and the central DB connection is restored.
        // Required for long-lived processes (php artisan serve / Octane).
        $middleware->append(\App\Http\Middleware\EnsureTenancyEnded::class);

        // Register named middleware aliases
        $middleware->alias([
            'superadmin'           => \App\Http\Middleware\SuperAdmin::class,
            'tenant.auth'          => \App\Http\Middleware\TenantAuth::class,  // OWASP A01: Now exists
            'initialize.tenancy'   => \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
            'prevent.access.central' => \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class,
            'module.permission'    => \App\Http\Middleware\CheckModulePermission::class,
            'brute.force'          => \App\Http\Middleware\PreventBruteForce::class, // OWASP A07
            'api.rate'             => \App\Http\Middleware\ApiSecurityRateLimiter::class, // OWASP A04
        ]);

        // Tenant web group — applied via RouteServiceProvider for tenant.php routes
        $middleware->group('tenant_web', [
            \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
            \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class,
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'api/*',
            'website-settings/test-sms',
        ]);

        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
