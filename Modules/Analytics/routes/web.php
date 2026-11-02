<?php

use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Modules\Analytics\Http\Controllers\Dashboard\StatsController;
use Modules\Analytics\Http\Controllers\Front\WhatsAppRedirectController;

// Public site: /ar/go/whatsapp/{slug}, /en/go/whatsapp/{slug} (counts the click, then redirects to wa.me)
Route::group([
    'prefix' => LaravelLocalization::setLocale(),
    'middleware' => ['localeRedirect'],
    'as' => 'front.',
], function () {
    Route::get('/go/whatsapp/{slug}', WhatsAppRedirectController::class)->name('products.whatsapp');
});

// Dashboard: manual pruning of old counters (the host may have no cron)
Route::prefix(LaravelLocalization::setLocale().'/admin')->name('admin.')->middleware(['admin.locale', 'auth:admin'])->group(function () {
    Route::post('/analytics/prune', [StatsController::class, 'prune'])->name('analytics.prune');
});
