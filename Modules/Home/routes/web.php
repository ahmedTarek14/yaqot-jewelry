<?php

use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Modules\Home\Http\Controllers\Dashboard\HomeController as DashboardHomeController;
use Modules\Home\Http\Controllers\Front\HomeController;

// Public site: Arabic at the root, English under /en.
Route::group([
    'prefix' => LaravelLocalization::setLocale(),
    'middleware' => ['localizationRedirect'],
    'as' => 'front.',
], function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
});

// Dashboard
Route::prefix('admin')->name('admin.')->middleware(['admin.locale', 'auth:admin'])->group(function () {
    Route::get('/', [DashboardHomeController::class, 'index'])->name('dashboard');
});
