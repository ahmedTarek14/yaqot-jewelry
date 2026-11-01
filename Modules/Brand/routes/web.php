<?php

use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Modules\Brand\Http\Controllers\Dashboard\BrandController;
use Modules\Brand\Http\Controllers\Front\BrandController as FrontBrandController;

// Public site: /ar/brands/{slug}, /en/brands/{slug}
Route::group([
    'prefix' => LaravelLocalization::setLocale(),
    'middleware' => ['localeRedirect'],
    'as' => 'front.',
], function () {
    Route::get('/brands/{slug}', [FrontBrandController::class, 'show'])->name('brands.show');
});

// Dashboard: /ar/admin/brands, /en/admin/brands
Route::prefix(LaravelLocalization::setLocale().'/admin')->name('admin.')->middleware(['admin.locale', 'auth:admin'])->group(function () {
    Route::controller(BrandController::class)->prefix('brands')->name('brands.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{brand}/edit', 'edit')->name('edit');
        Route::put('/{brand}', 'update')->name('update');
        Route::patch('/{brand}/active', 'toggleActive')->name('toggle-active');
        Route::delete('/{brand}', 'destroy')->name('destroy');
    });
});
