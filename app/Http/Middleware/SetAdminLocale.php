<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin routes are not localized: the dashboard is always Arabic (RTL),
 * whatever the public site's default locale is.
 */
class SetAdminLocale
{
    public const LOCALE = 'ar';

    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale(self::LOCALE);

        return $next($request);
    }
}
