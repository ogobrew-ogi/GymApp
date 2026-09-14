<?php

declare(strict_types=1);

use App\Http\Middleware\EnsurePasswordIsChanged;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Every route in this application is private (see UX/UI Spec: no
        // public pages). The 'web' group already includes session/CSRF/etc.
        // Route-level 'auth' middleware is applied explicitly per route
        // rather than globally, to keep the health-check endpoint reachable.
        $middleware->alias([
            'password.change' => EnsurePasswordIsChanged::class,
        ]);

        // Trust the reverse proxy in front of the app (e.g. a Cloudflare
        // Tunnel used for testing) so Laravel reads the original
        // client-facing scheme from X-Forwarded-Proto instead of
        // assuming the plain-HTTP connection it actually receives —
        // otherwise generated asset URLs come back http:// on a page
        // served over https://, which browsers block as mixed content.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
