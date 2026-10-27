<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin routes are not localized: the dashboard is always shown in the
 * application's default locale (Arabic).
 */
class SetAdminLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale(config('app.locale'));

        return $next($request);
    }
}
