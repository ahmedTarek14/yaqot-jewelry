<?php

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Modules\Page\Http\Controllers\Front\PageController;
use Modules\Page\Http\Controllers\Front\SitemapController;

// Crawler files at the site root (no language prefix, no session or cookies).
Route::withoutMiddleware([StartSession::class, ShareErrorsFromSession::class, VerifyCsrfToken::class, AddQueuedCookiesToResponse::class, EncryptCookies::class])
    ->group(function () {
        Route::get('/sitemap.xml', [SitemapController::class, 'sitemap'])->name('sitemap');
        Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');
    });

// Public site: /ar/about, /en/contact, ...
Route::group([
    'prefix' => LaravelLocalization::setLocale(),
    'middleware' => ['localeRedirect'],
    'as' => 'front.',
], function () {
    Route::get('/about', [PageController::class, 'about'])->name('about');
    Route::get('/contact', [PageController::class, 'contact'])->name('contact');
    Route::get('/privacy-policy', [PageController::class, 'privacy'])->name('privacy');
});
