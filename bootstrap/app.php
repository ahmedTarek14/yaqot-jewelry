<?php

use App\Http\Middleware\AdminLocale;
use App\Http\Middleware\RedirectToLocalizedUrl;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Modules\Auth\Http\Middleware\EndStaleAdminSession;

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

        // ===== Security headers =====
        // Global and outermost, so error pages, unknown URLs and the maintenance 503 (which Laravel
        // answers in an earlier global middleware) carry them too (see the class).
        $middleware->prepend(SecurityHeaders::class);

        // ===== Redirects =====
        // Only the admin dashboard requires authentication. route() builds the URL
        // in the request's locale (/ar/admin/login or /en/admin/login).
        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));

        // ===== Middleware Aliases =====
        // AdminLocale must redirect unprefixed /admin URLs before auth redirects a guest.
        $middleware->prependToPriorityList(
            before: AuthenticatesRequests::class,
            prepend: AdminLocale::class,
        );

        // A password change ends the admin's other sessions (see the class).
        // It must run before `auth:admin`, which otherwise sorts ahead of it.
        $middleware->web(append: [EndStaleAdminSession::class]);
        $middleware->prependToPriorityList(
            before: AuthenticatesRequests::class,
            prepend: EndStaleAdminSession::class,
        );

        $middleware->alias([
            'admin.locale' => AdminLocale::class,
            'localeRedirect' => RedirectToLocalizedUrl::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
