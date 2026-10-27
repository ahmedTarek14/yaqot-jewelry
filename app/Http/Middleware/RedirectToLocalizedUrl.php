<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Symfony\Component\HttpFoundation\Response;

/**
 * Every public URL carries its locale (/ar/..., /en/...). A GET request without
 * one (e.g. "/" or "/catalog") is redirected to the same path in the default
 * locale. Deterministic on purpose: no session, cookie or browser-language
 * guessing, so "/" always means "/en" (also the hreflang x-default).
 */
class RedirectToLocalizedUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethodSafe() || LaravelLocalization::checkLocaleInSupportedLocales($request->segment(1))) {
            return $next($request);
        }

        $url = LaravelLocalization::getLocalizedURL(LaravelLocalization::getDefaultLocale(), $request->url());

        // Re-attach the raw query string: fullUrl() would sort the parameters
        // and collapse repeated keys (?tag=1&tag=2).
        $query = $request->server('QUERY_STRING');

        return redirect()->to($query ? "$url?$query" : $url);
    }
}
