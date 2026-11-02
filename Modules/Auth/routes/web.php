<?php

use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Modules\Auth\Http\Controllers\Dashboard\AccountController;
use Modules\Auth\Http\Controllers\Dashboard\AdminController;
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

    Route::middleware('auth:admin')->group(function () {
        // "My account": the signed-in admin's own name, email and password.
        Route::controller(AccountController::class)->prefix('account')->name('account.')->group(function () {
            Route::get('/', 'edit')->name('edit');
            Route::put('/', 'update')->name('update');
        });

        // Admin users. All admins have equal rights.
        Route::controller(AdminController::class)->prefix('admins')->name('admins.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{admin}/edit', 'edit')->name('edit');
            Route::put('/{admin}', 'update')->name('update');
            Route::patch('/{admin}/active', 'toggleActive')->name('toggle-active');
            Route::delete('/{admin}', 'destroy')->name('destroy');
        });
    });
});
