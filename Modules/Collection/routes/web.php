<?php

use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Modules\Collection\Http\Controllers\Dashboard\CollectionController;
use Modules\Collection\Http\Controllers\Front\CollectionController as FrontCollectionController;

// Public site: /ar/collections/{slug}, /en/collections/{slug}
Route::group([
    'prefix' => LaravelLocalization::setLocale(),
    'middleware' => ['localeRedirect'],
    'as' => 'front.',
], function () {
    Route::get('/collections/{slug}', [FrontCollectionController::class, 'show'])->name('collections.show');
});

// Dashboard: /ar/admin/collections, /en/admin/collections
Route::prefix(LaravelLocalization::setLocale().'/admin')->name('admin.')->middleware(['admin.locale', 'auth:admin'])->group(function () {
    Route::controller(CollectionController::class)->prefix('collections')->name('collections.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{collection}/edit', 'edit')->name('edit');
        Route::put('/{collection}', 'update')->name('update');
        Route::patch('/{collection}/active', 'toggleActive')->name('toggle-active');
        Route::patch('/{collection}/homepage', 'toggleHomepage')->name('toggle-homepage');
        Route::delete('/{collection}', 'destroy')->name('destroy');
    });
});
