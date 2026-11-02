<?php

namespace Modules\Analytics\Support;

use Illuminate\Http\Request;

/**
 * Decides whether a request is a real visit worth counting. Nothing about the visitor is
 * kept: the User-Agent is only inspected, never stored.
 */
final class BotDetector
{
    /**
     * A missing User-Agent counts as automated; so does any that contains a configured token.
     */
    public static function isBot(?string $userAgent): bool
    {
        $userAgent = strtolower(trim((string) $userAgent));

        if ($userAgent === '') {
            return true;
        }

        foreach ((array) config('analytics.bot_tokens', []) as $token) {
            if ($token !== '' && str_contains($userAgent, strtolower($token))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Browser prefetch / prerender requests are not a visit yet; HEAD requests never are.
     */
    public static function isPrefetch(Request $request): bool
    {
        if ($request->isMethod('HEAD')) {
            return true;
        }

        $purpose = strtolower($request->header('Purpose', '').' '.$request->header('Sec-Purpose', '').' '.$request->header('X-Moz', ''));

        return str_contains($purpose, 'prefetch') || str_contains($purpose, 'prerender') || str_contains($purpose, 'preview');
    }

    /**
     * True when the request is a real person's visit.
     */
    public static function countsRequest(Request $request): bool
    {
        return ! self::isPrefetch($request) && ! self::isBot($request->userAgent());
    }
}
