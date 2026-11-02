<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security headers on every response (global middleware, so 404s and errors get them too).
 *
 * - nosniff, a strict referrer policy, no framing (X-Frame-Options plus the CSP's
 *   frame-ancestors), a Permissions-Policy that switches off the browser features the site
 *   never uses, and Cross-Origin-Opener-Policy.
 * - Strict-Transport-Security only on https (never sent over http, where it is ignored anyway),
 *   without includeSubDomains/preload: the site may live on a subdomain of its host.
 * - A Content-Security-Policy for pages: scripts only from this site, plus the one inline script
 *   (the layout's progressive-enhancement line) through a per-request nonce, available in views
 *   as csp_nonce(). Google's hosts are allowed only on a page that renders the Google Analytics
 *   cookie notice (the Analytics module sets ANALYTICS_ATTRIBUTE when a measurement id is set).
 *   Inline event handlers are refused except the admin theme's two (see policy()).
 *   Style attributes stay allowed (a few components pass CSS variables in style="..."); style
 *   elements and stylesheets must come from this site. With APP_DEBUG on there is no CSP: the
 *   debug error page needs inline scripts, and debug is never on in production.
 */
class SecurityHeaders
{
    public const NONCE_ATTRIBUTE = 'csp.nonce';

    public const ANALYTICS_ATTRIBUTE = 'csp.analytics';

    /** Six months. */
    private const HSTS_MAX_AGE = 15552000;

    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set(self::NONCE_ATTRIBUTE, base64_encode(random_bytes(16)));

        $response = $next($request);

        $headers = $response->headers;
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), browsing-topics=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        if ($this->isHttps($request)) {
            $headers->set('Strict-Transport-Security', 'max-age='.self::HSTS_MAX_AGE);
        }

        if (! config('app.debug') && $this->isPage($response)) {
            $headers->set('Content-Security-Policy', $this->policy($request));
        }

        header_remove('X-Powered-By');

        return $response;
    }

    /**
     * Behind a proxy that ends TLS, PHP sees plain http: the forwarded protocol tells.
     */
    private function isHttps(Request $request): bool
    {
        return $request->isSecure() || strtolower((string) $request->headers->get('X-Forwarded-Proto')) === 'https';
    }

    private function isPage(Response $response): bool
    {
        $type = (string) $response->headers->get('Content-Type');

        return ! $response->isRedirection() && ($type === '' || str_starts_with($type, 'text/html'));
    }

    private function policy(Request $request): string
    {
        $analytics = (bool) $request->attributes->get(self::ANALYTICS_ATTRIBUTE, false);
        $nonce = (string) $request->attributes->get(self::NONCE_ATTRIBUTE);

        $directives = [
            "default-src 'self'",
            "script-src 'self' 'nonce-$nonce'".($analytics ? ' https://www.googletagmanager.com' : ''),
            // The admin theme's script gives input-group inputs onfocus="focused(this)" and
            // onfocusout="defocused(this)" (the focus ring): only those two exact strings are allowed.
            "script-src-attr 'unsafe-hashes' 'sha256-7cDVV1uHkbASbv4FVn41IxUCmo4UVfnKRZGjc8Yvwu8=' 'sha256-VvIRyo9VuGd89iPQaBub4JKTqGe4m3kW51CjBfUCzlA='",
            "style-src 'self'",
            "style-src-attr 'unsafe-inline'",
            // blob: is for the admin's image previews (URL.createObjectURL of a file the admin just chose).
            "img-src 'self' data: blob:".($analytics ? ' https://*.google-analytics.com https://*.googletagmanager.com' : ''),
            "font-src 'self'",
            "connect-src 'self'".($analytics ? ' https://*.google-analytics.com https://*.analytics.google.com https://*.googletagmanager.com' : ''),
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ];

        if ($this->isHttps($request)) {
            $directives[] = 'upgrade-insecure-requests';
        }

        return implode('; ', $directives);
    }
}
