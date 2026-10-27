<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\Dashboard\AuthController;

Route::prefix('admin')->name('admin.')->middleware('admin.locale')->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::middleware('guest:admin')->group(function () {
            Route::get('/login', 'showLoginForm')->name('login');
            Route::post('/login', 'login')->name('login.submit');
        });

        Route::post('/logout', 'logout')->middleware('auth:admin')->name('logout');
    });
});
