<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin URLs are localized like the public site: /ar/admin/... and /en/admin/...
 *
 * - On a localized admin URL, remember its locale in the session
 *   (LaravelLocalization already set the app locale from the prefix).
 * - An unprefixed /admin/... GET redirects to the locale the admin used last,
 *   defaulting to Arabic. Unprefixed non-GET requests are rejected (404).
 */
class AdminLocale
{
    public const DEFAULT = 'ar';

    public const SESSION_KEY = 'admin_locale';

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->segment(1);

        if (LaravelLocalization::checkLocaleInSupportedLocales($locale)) {
            $request->session()->put(self::SESSION_KEY, $locale);

            return $next($request);
        }

        abort_unless($request->isMethodSafe(), 404);

        $target = $request->session()->get(self::SESSION_KEY);
        if (! LaravelLocalization::checkLocaleInSupportedLocales($target)) {
            $target = self::DEFAULT;
        }

        $url = LaravelLocalization::getLocalizedURL($target, $request->url());
        $query = $request->server('QUERY_STRING');

        return redirect()->to($query ? "$url?$query" : $url);
    }
}
