<?php

use App\Http\Middleware\RedirectToLocalizedUrl;
use App\Http\Middleware\SetAdminLocale;
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

        // ===== Trusted hosts =====
        // Outside local/testing, only APP_URL's host (and subdomains) is accepted,
        // so absolute URLs and redirects can't be built from a forged Host header.
        $middleware->trustHosts();

        // ===== Redirects =====
        // Only the admin dashboard requires authentication.
        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));

        // ===== Middleware Aliases =====
        $middleware->alias([
            'admin.locale' => SetAdminLocale::class,
            'localeRedirect' => RedirectToLocalizedUrl::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
