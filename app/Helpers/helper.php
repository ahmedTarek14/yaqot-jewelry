<?php

use App\Http\Middleware\SecurityHeaders;
use Modules\Setting\Services\SettingService;

if (! function_exists('csp_nonce')) {
    /**
     * This request's Content-Security-Policy nonce (App\Http\Middleware\SecurityHeaders): put it
     * on the page's one inline <script>. Empty outside a request that went through the middleware.
     */
    function csp_nonce(): string
    {
        return (string) request()->attributes->get(SecurityHeaders::NONCE_ATTRIBUTE, '');
    }
}

if (! function_exists('versioned_asset')) {
    /**
     * URL of a file in public/ with its modification time appended,
     * so browsers re-fetch static assets after a deploy.
     */
    function versioned_asset(string $path): string
    {
        $path = ltrim($path, '/');
        $file = public_path($path);

        return asset($path).(is_file($file) ? '?v='.filemtime($file) : '');
    }
}

if (! function_exists('setting')) {
    /**
     * A site setting in the current locale (SettingService::get()), or $default when it
     * is unset or empty. Read it in controllers and view composers, not in Blade.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return app(SettingService::class)->get($key, $default);
    }
}
