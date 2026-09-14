<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Per UX/UI Spec §9.9: this is the one screen allowed to block navigation
 * app-wide, since it's a security requirement rather than a convenience.
 * Every authenticated route runs through this except the change-password
 * screen itself and logout.
 */
final class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->must_change_password
            && ! $request->routeIs('password.change')
            && ! $request->routeIs('logout')
        ) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }
}
