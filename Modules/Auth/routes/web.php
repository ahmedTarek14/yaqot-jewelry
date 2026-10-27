<?php

use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Modules\Auth\Http\Controllers\Dashboard\AuthController;

// Dashboard auth: /ar/admin/login, /en/admin/login (unprefixed /admin/... redirects, see AdminLocale).
Route::prefix(LaravelLocalization::setLocale().'/admin')->name('admin.')->middleware('admin.locale')->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::middleware('guest:admin')->group(function () {
            Route::get('/login', 'showLoginForm')->name('login');
            Route::post('/login', 'login')->name('login.submit');
        });

        Route::post('/logout', 'logout')->middleware('auth:admin')->name('logout');
    });
});
